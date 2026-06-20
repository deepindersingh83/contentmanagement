<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ImportTemplate;
use phpseclib3\Net\SFTP;

/**
 * Resolves an import template's source to a local file path, downloading from
 * URL/FTP/sFTP as needed and unpacking a zip archive when configured.
 */
final class FeedFetcher
{
    public function __construct(private readonly string $projectDir)
    {
    }

    /**
     * @return array{path: string, cleanup: bool}
     *
     * @throws \RuntimeException on any fetch failure
     */
    public function fetch(ImportTemplate $template): array
    {
        [$path, $cleanup] = match ($template->getSource()) {
            'direct' => [$this->directPath($template), false],
            'url' => [$this->downloadUrl($template), true],
            'ftp' => [$this->downloadFtp($template), true],
            'sftp' => [$this->downloadSftp($template), true],
            default => throw new \RuntimeException('Unsupported source.'),
        };

        if ($template->isZipArchive()) {
            $extracted = $this->extractFromZip($path, $template->getFileFormat());
            if ($cleanup) {
                @unlink($path);
            }

            return ['path' => $extracted, 'cleanup' => true];
        }

        return ['path' => $path, 'cleanup' => $cleanup];
    }

    private function directPath(ImportTemplate $template): string
    {
        $stored = $template->getStoredFilename();
        $path = $stored !== null ? $this->uploadDir().'/'.$stored : null;
        if ($path === null || !is_file($path)) {
            throw new \RuntimeException('The uploaded file for this template could not be found.');
        }

        return $path;
    }

    private function downloadUrl(ImportTemplate $template): string
    {
        $url = $template->getSourceUrl();
        if ($url === null || $url === '') {
            throw new \RuntimeException('No File URL is set on this import.');
        }

        return $this->curlDownload($url);
    }

    private function downloadFtp(ImportTemplate $template): string
    {
        $host = $template->getFtpServer();
        $remote = $template->getFtpPath();
        if (!$host || !$remote) {
            throw new \RuntimeException('FTP server and absolute path are required.');
        }
        $port = $template->getFtpPort() ?: 21;
        $url = sprintf('ftp://%s:%d/%s', $host, $port, ltrim($remote, '/'));
        $userpwd = $template->getFtpUsername() !== null
            ? $template->getFtpUsername().':'.($template->getFtpPassword() ?? '')
            : null;

        return $this->curlDownload($url, $userpwd, $template->isFtpPassiveMode());
    }

    private function downloadSftp(ImportTemplate $template): string
    {
        $host = $template->getFtpServer();
        $remote = $template->getFtpPath();
        if (!$host || !$remote) {
            throw new \RuntimeException('sFTP server and absolute path are required.');
        }
        $sftp = new SFTP($host, $template->getFtpPort() ?: 22);
        if (!$sftp->login((string) $template->getFtpUsername(), (string) $template->getFtpPassword())) {
            throw new \RuntimeException('sFTP login failed.');
        }
        $contents = $sftp->get($remote);
        if ($contents === false || !is_string($contents)) {
            throw new \RuntimeException('Could not read the remote sFTP file.');
        }

        $tmp = $this->tempFile();
        file_put_contents($tmp, $contents);

        return $tmp;
    }

    private function curlDownload(string $url, ?string $userpwd = null, bool $passive = true): string
    {
        $tmp = $this->tempFile();
        $fp = fopen($tmp, 'w');
        if ($fp === false) {
            throw new \RuntimeException('Could not create a temporary file.');
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_FAILONERROR => true,
            CURLOPT_FTP_USE_EPSV => $passive,
        ]);
        if ($userpwd !== null) {
            curl_setopt($ch, CURLOPT_USERPWD, $userpwd);
        }
        $ok = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if ($ok === false) {
            @unlink($tmp);
            throw new \RuntimeException('Download failed: '.$error);
        }

        return $tmp;
    }

    private function extractFromZip(string $zipPath, string $format): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Could not open the zip archive.');
        }

        $target = null;
        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $name = (string) $zip->getNameIndex($i);
            if (str_ends_with(strtolower($name), '.'.$format)) {
                $target = $name;
                break;
            }
        }
        if ($target === null) {
            $zip->close();
            throw new \RuntimeException(sprintf('No .%s file found inside the zip archive.', $format));
        }

        $tmp = $this->tempFile();
        $stream = $zip->getStream($target);
        if ($stream === false) {
            $zip->close();
            throw new \RuntimeException('Could not read the file from the zip archive.');
        }
        file_put_contents($tmp, stream_get_contents($stream));
        fclose($stream);
        $zip->close();

        return $tmp;
    }

    private function uploadDir(): string
    {
        return $this->projectDir.'/var/uploads/imports';
    }

    private function tempFile(): string
    {
        $dir = $this->projectDir.'/var/tmp';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir.'/feed-'.bin2hex(random_bytes(8));
    }
}
