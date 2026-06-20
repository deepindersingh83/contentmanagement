<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ImportTemplate;
use App\Entity\User;
use App\Repository\ImportTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/import-templates')]
class ImportTemplateController extends AbstractController
{
    private const SOURCES = ['direct', 'url', 'ftp', 'sftp'];
    private const FORMATS = ['xlsx', 'xls', 'csv', 'xml'];
    private const KEY_FIELDS = ['title', 'sku', 'barcode', 'model', 'id'];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ImportTemplateRepository $templates,
        private readonly \App\Repository\SupplierRepository $suppliers,
        private readonly \App\Service\ImportRunner $importRunner,
        private readonly \App\Service\FeedFetcher $feedFetcher,
        private readonly \App\Service\FeedReader $feedReader,
    ) {
    }

    /**
     * Dry-run the import: parse + map the first rows and return a preview
     * without writing anything ("Test import").
     */
    #[Route('/{id}/test', name: 'api_import_templates_test', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function test(#[CurrentUser] ?User $user, ImportTemplate $template): JsonResponse
    {
        if ($user === null || $template->getOwner()?->getId() !== $user->getId()) {
            return $this->json(['message' => 'Not found'], 404);
        }

        $result = $this->importRunner->run($template, true);
        if (isset($result['error'])) {
            return $this->json(['message' => $result['error']], 422);
        }

        return $this->json($result);
    }

    /**
     * Execute the import: upsert supplier offers from the feed and match them to
     * products ("Import"). The supplier must be set on the template.
     */
    #[Route('/{id}/run', name: 'api_import_templates_run', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function runImport(#[CurrentUser] ?User $user, ImportTemplate $template): JsonResponse
    {
        if ($user === null || $template->getOwner()?->getId() !== $user->getId()) {
            return $this->json(['message' => 'Not found'], 404);
        }
        if ($template->getSupplierRef() === null) {
            return $this->json(['message' => 'Assign a supplier to this import before running it.'], 422);
        }

        $result = $this->importRunner->run($template, false);
        if (isset($result['error'])) {
            return $this->json(['message' => $result['error']], 422);
        }

        return $this->json($result);
    }

    #[Route('', name: 'api_import_templates_list', methods: ['GET'])]
    public function list(#[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $data = array_map(
            fn (ImportTemplate $t) => $this->serialize($t),
            $this->templates->findByOwner($user),
        );

        return $this->json($data);
    }

    #[Route('/{id}', name: 'api_import_templates_get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(#[CurrentUser] ?User $user, ImportTemplate $template): JsonResponse
    {
        if ($user === null || $template->getOwner()?->getId() !== $user->getId()) {
            return $this->json(['message' => 'Not found'], 404);
        }

        return $this->json($this->serialize($template));
    }

    /**
     * Creates an import template. Accepts multipart/form-data so the source
     * file can be uploaded alongside the configuration fields.
     */
    #[Route('', name: 'api_import_templates_create', methods: ['POST'])]
    public function create(#[CurrentUser] ?User $user, Request $request): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $name = trim((string) $request->request->get('name', ''));
        if ($name === '') {
            return $this->json(['message' => 'Template name is required.'], 422);
        }

        $source = (string) $request->request->get('source', 'direct');
        $fileFormat = (string) $request->request->get('fileFormat', 'xlsx');
        $keyField = (string) $request->request->get('keyField', 'title');

        if (!in_array($source, self::SOURCES, true)) {
            return $this->json(['message' => 'Invalid source.'], 422);
        }
        if (!in_array($fileFormat, self::FORMATS, true)) {
            return $this->json(['message' => 'Invalid file format.'], 422);
        }
        if (!in_array($keyField, self::KEY_FIELDS, true)) {
            return $this->json(['message' => 'Invalid product identification key.'], 422);
        }

        $template = new ImportTemplate();
        $template->setOwner($user);
        $template->setName($name);
        $template->setSupplier($this->nullableString($request->request->get('supplier')));

        // Link to a Supplier record when provided (keeps the name string in sync).
        $supplierId = $request->request->get('supplierId');
        if ($supplierId !== null && $supplierId !== '') {
            $supplier = $this->suppliers->find((int) $supplierId);
            if ($supplier !== null) {
                $template->setSupplierRef($supplier);
                $template->setSupplier($supplier->getName());
            }
        }
        $template->setSource($source);
        $template->setSourceUrl($this->nullableString($request->request->get('sourceUrl')));
        $template->setFileFormat($fileFormat);
        $template->setKeyField($keyField);
        $template->setFirstRowHeaders($this->boolParam($request, 'firstRowHeaders', true));
        $template->setZipArchive($this->boolParam($request, 'zipArchive', false));
        $template->setImportTranslations($this->boolParam($request, 'importTranslations', false));

        // CSV delimiter (only relevant for csv).
        $template->setDelimiter($fileFormat === 'csv' ? $this->nullableString($request->request->get('delimiter')) : null);

        // FTP / sFTP connection details.
        $template->setFtpServer($this->nullableString($request->request->get('ftpServer')));
        $template->setFtpUsername($this->nullableString($request->request->get('ftpUsername')));
        $template->setFtpPassword($this->nullableString($request->request->get('ftpPassword')));
        $port = $request->request->get('ftpPort');
        $template->setFtpPort(($port !== null && $port !== '') ? (int) $port : null);
        $template->setFtpPath($this->nullableString($request->request->get('ftpPath')));
        $template->setFtpPassiveMode($this->boolParam($request, 'ftpPassiveMode', true));
        $template->setRemoveAfterImport($this->boolParam($request, 'removeAfterImport', false));

        // Store the uploaded file (only meaningful for a direct upload).
        $file = $request->files->get('file');
        if ($file !== null) {
            $template->setOriginalFilename($file->getClientOriginalName());
            $stored = bin2hex(random_bytes(16)).'-'.preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());
            $file->move($this->uploadDir(), $stored);
            $template->setStoredFilename($stored);
        }

        $this->em->persist($template);
        $this->em->flush();

        return $this->json($this->serialize($template), 201);
    }

    /**
     * Returns the column names detected from the uploaded source file, used to
     * populate the field-mapping dropdowns on the Import settings screen.
     * CSV is parsed directly; other formats need a parser and return [].
     */
    #[Route('/{id}/columns', name: 'api_import_templates_columns', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function columns(#[CurrentUser] ?User $user, ImportTemplate $template): JsonResponse
    {
        if ($user === null || $template->getOwner()?->getId() !== $user->getId()) {
            return $this->json(['message' => 'Not found'], 404);
        }

        try {
            $feed = $this->feedFetcher->fetch($template);
        } catch (\RuntimeException $e) {
            return $this->json(['columns' => [], 'note' => $e->getMessage()]);
        }

        try {
            $parsed = $this->feedReader->read(
                $feed['path'],
                $template->getFileFormat(),
                $template->getDelimiter(),
                $template->isFirstRowHeaders(),
            );
        } finally {
            if ($feed['cleanup']) {
                @unlink($feed['path']);
            }
        }

        $note = $parsed['headers'] === [] ? 'No columns could be detected from the feed.' : null;

        return $this->json(['columns' => $parsed['headers'], 'note' => $note]);
    }

    /**
     * Set the schedule frequency (manual | hourly | daily | weekly) and compute
     * the next run time.
     */
    #[Route('/{id}/schedule', name: 'api_import_templates_schedule', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function schedule(#[CurrentUser] ?User $user, ImportTemplate $template, Request $request): JsonResponse
    {
        if ($user === null || $template->getOwner()?->getId() !== $user->getId()) {
            return $this->json(['message' => 'Not found'], 404);
        }

        /** @var array<string, mixed> $data */
        $data = json_decode($request->getContent() ?: '{}', true) ?? [];
        $freq = (string) ($data['scheduleFrequency'] ?? 'manual');
        if (!in_array($freq, ['manual', 'hourly', 'daily', 'weekly'], true)) {
            return $this->json(['message' => 'Invalid schedule frequency.'], 422);
        }

        $template->setScheduleFrequency($freq);
        $interval = $template->intervalForSchedule();
        $template->setNextRunAt($interval !== null ? (new \DateTimeImmutable())->add($interval) : null);
        $this->em->flush();

        return $this->json($this->serialize($template));
    }

    /**
     * Saves the field-to-column mapping built on the Import settings screen.
     */
    #[Route('/{id}/mapping', name: 'api_import_templates_mapping', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function saveMapping(#[CurrentUser] ?User $user, ImportTemplate $template, Request $request): JsonResponse
    {
        if ($user === null || $template->getOwner()?->getId() !== $user->getId()) {
            return $this->json(['message' => 'Not found'], 404);
        }

        /** @var array<string, mixed> $data */
        $data = json_decode($request->getContent() ?: '{}', true) ?? [];
        $mapping = is_array($data['mapping'] ?? null) ? $data['mapping'] : [];

        $template->setMapping($mapping);
        $this->em->flush();

        return $this->json($this->serialize($template));
    }

    /**
     * Duplicates an existing template (config only — not the stored file).
     */
    #[Route('/{id}/duplicate', name: 'api_import_templates_duplicate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function duplicate(#[CurrentUser] ?User $user, ImportTemplate $source): JsonResponse
    {
        if ($user === null || $source->getOwner()?->getId() !== $user->getId()) {
            return $this->json(['message' => 'Not found'], 404);
        }

        $copy = new ImportTemplate();
        $copy->setOwner($user);
        $copy->setName($source->getName().' copy');
        $copy->setSupplier($source->getSupplier());
        $copy->setSupplierRef($source->getSupplierRef());
        $copy->setSource($source->getSource());
        $copy->setSourceUrl($source->getSourceUrl());
        $copy->setFileFormat($source->getFileFormat());
        $copy->setKeyField($source->getKeyField());
        $copy->setFirstRowHeaders($source->isFirstRowHeaders());
        $copy->setZipArchive($source->isZipArchive());
        $copy->setImportTranslations($source->isImportTranslations());
        $copy->setDelimiter($source->getDelimiter());
        $copy->setFtpServer($source->getFtpServer());
        $copy->setFtpUsername($source->getFtpUsername());
        $copy->setFtpPassword($source->getFtpPassword());
        $copy->setFtpPort($source->getFtpPort());
        $copy->setFtpPath($source->getFtpPath());
        $copy->setFtpPassiveMode($source->isFtpPassiveMode());
        $copy->setRemoveAfterImport($source->isRemoveAfterImport());
        $copy->setMapping($source->getMapping());

        $this->em->persist($copy);
        $this->em->flush();

        return $this->json($this->serialize($copy), 201);
    }

    /**
     * Downloads the template configuration as a JSON file.
     */
    #[Route('/{id}/download', name: 'api_import_templates_download', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function download(#[CurrentUser] ?User $user, ImportTemplate $template): JsonResponse
    {
        if ($user === null || $template->getOwner()?->getId() !== $user->getId()) {
            return $this->json(['message' => 'Not found'], 404);
        }

        $response = $this->json($this->serialize($template));
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '_', $template->getName()).'.json';
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }

    #[Route('/{id}', name: 'api_import_templates_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(#[CurrentUser] ?User $user, ImportTemplate $template): JsonResponse
    {
        if ($user === null || $template->getOwner()?->getId() !== $user->getId()) {
            return $this->json(['message' => 'Not found'], 404);
        }

        if ($template->getStoredFilename() !== null) {
            $path = $this->uploadDir().'/'.$template->getStoredFilename();
            if (is_file($path)) {
                @unlink($path);
            }
        }

        $this->em->remove($template);
        $this->em->flush();

        return $this->json(null, 204);
    }

    private function uploadDir(): string
    {
        $dir = $this->getParameter('kernel.project_dir').'/var/uploads/imports';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : $value;
    }

    private function boolParam(Request $request, string $key, bool $default): bool
    {
        if (!$request->request->has($key)) {
            return $default;
        }

        return filter_var($request->request->get($key), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(ImportTemplate $t): array
    {
        return [
            'id' => $t->getId(),
            'name' => $t->getName(),
            'supplier' => $t->getSupplier(),
            'supplierId' => $t->getSupplierRef()?->getId(),
            'source' => $t->getSource(),
            'sourceUrl' => $t->getSourceUrl(),
            'fileFormat' => $t->getFileFormat(),
            'keyField' => $t->getKeyField(),
            'firstRowHeaders' => $t->isFirstRowHeaders(),
            'zipArchive' => $t->isZipArchive(),
            'importTranslations' => $t->isImportTranslations(),
            'delimiter' => $t->getDelimiter(),
            'ftpServer' => $t->getFtpServer(),
            'ftpUsername' => $t->getFtpUsername(),
            'ftpPort' => $t->getFtpPort(),
            'ftpPath' => $t->getFtpPath(),
            'ftpPassiveMode' => $t->isFtpPassiveMode(),
            'removeAfterImport' => $t->isRemoveAfterImport(),
            'mapping' => $t->getMapping(),
            'scheduleFrequency' => $t->getScheduleFrequency(),
            'lastRunAt' => $t->getLastRunAt()?->format(\DateTimeInterface::ATOM),
            'nextRunAt' => $t->getNextRunAt()?->format(\DateTimeInterface::ATOM),
            'originalFilename' => $t->getOriginalFilename(),
            'createdAt' => $t->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
