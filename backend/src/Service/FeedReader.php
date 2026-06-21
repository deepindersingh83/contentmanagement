<?php

declare(strict_types=1);

namespace App\Service;

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Reads a feed file into a uniform shape regardless of format:
 *   ['headers' => string[], 'rows' => array<int, array<string,string>>]
 * Supports CSV, XLSX/XLS and (heuristically) XML product feeds.
 */
final class FeedReader
{
    /**
     * @return array{headers: list<string>, rows: array<int, array<string, string>>}
     */
    public function read(string $path, string $format, ?string $delimiter, bool $firstRowHeaders): array
    {
        return match ($format) {
            'csv' => $this->readCsv($path, $delimiter ?: ',', $firstRowHeaders),
            'xlsx', 'xls' => $this->readSpreadsheet($path, $firstRowHeaders),
            'xml' => $this->readXml($path),
            default => ['headers' => [], 'rows' => []],
        };
    }

    /**
     * @return array{headers: list<string>, rows: array<int, array<string, string>>}
     */
    private function readCsv(string $path, string $delimiter, bool $firstRowHeaders): array
    {
        if ($delimiter === '\t') {
            $delimiter = "\t";
        }
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return ['headers' => [], 'rows' => []];
        }

        $headers = [];
        $rows = [];
        $first = true;
        while (($line = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            if ($first) {
                $headers = $firstRowHeaders
                    ? array_map(fn ($h) => trim((string) $h), $line)
                    : array_map(fn ($i) => 'Column '.($i + 1), array_keys($line));
                $first = false;
                if ($firstRowHeaders) {
                    continue;
                }
            }
            $rows[] = $this->assoc($headers, $line);
        }
        fclose($handle);

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * @return array{headers: list<string>, rows: array<int, array<string, string>>}
     */
    private function readSpreadsheet(string $path, bool $firstRowHeaders): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($path)->getActiveSheet();
        /** @var array<int, array<int, mixed>> $data */
        $data = $sheet->toArray(null, true, false, false);
        if ($data === []) {
            return ['headers' => [], 'rows' => []];
        }

        $headerRow = $firstRowHeaders ? array_shift($data) : null;
        $headers = $headerRow !== null
            ? array_map(fn ($h) => trim((string) $h), $headerRow)
            : array_map(fn ($i) => 'Column '.($i + 1), array_keys($data[0] ?? []));

        $rows = [];
        foreach ($data as $line) {
            $rows[] = $this->assoc($headers, array_values($line));
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * Heuristic XML reader: finds the most frequently repeated element and
     * treats each occurrence as a row, flattening its child elements/attributes.
     *
     * @return array{headers: list<string>, rows: array<int, array<string, string>>}
     */
    private function readXml(string $path): array
    {
        $xml = @simplexml_load_file($path);
        if ($xml === false) {
            return ['headers' => [], 'rows' => []];
        }

        // Pick the element name that repeats most often as the record node.
        $counts = [];
        foreach ($xml->xpath('//*') ?: [] as $el) {
            $name = $el->getName();
            $counts[$name] = ($counts[$name] ?? 0) + 1;
        }
        arsort($counts);
        $recordName = null;
        foreach ($counts as $name => $count) {
            if ($count > 1) {
                $recordName = $name;
                break;
            }
        }
        $records = $recordName !== null ? ($xml->xpath('//'.$recordName) ?: []) : [$xml];

        $headers = [];
        $rows = [];
        foreach ($records as $record) {
            $flat = [];
            foreach ($record->attributes() ?? [] as $k => $v) {
                $flat[(string) $k] = trim((string) $v);
            }
            foreach ($record->children() as $child) {
                $key = $child->getName();
                if (!array_key_exists($key, $flat)) {
                    $flat[$key] = trim((string) $child);
                }
            }
            foreach (array_keys($flat) as $k) {
                if (!in_array($k, $headers, true)) {
                    $headers[] = $k;
                }
            }
            $rows[] = $flat;
        }

        // Normalise rows so every row has every header key.
        foreach ($rows as &$row) {
            foreach ($headers as $h) {
                $row[$h] ??= '';
            }
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * @param list<string> $headers
     * @param array<int, mixed> $line
     * @return array<string, string>
     */
    private function assoc(array $headers, array $line): array
    {
        $row = [];
        foreach ($headers as $i => $name) {
            $value = $line[$i] ?? '';
            $row[$name] = $value === null ? '' : trim((string) $value);
        }

        return $row;
    }
}
