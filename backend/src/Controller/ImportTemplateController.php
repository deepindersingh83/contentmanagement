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
    ) {
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
        $template->setSource($source);
        $template->setSourceUrl($this->nullableString($request->request->get('sourceUrl')));
        $template->setFileFormat($fileFormat);
        $template->setKeyField($keyField);
        $template->setFirstRowHeaders($this->boolParam($request, 'firstRowHeaders', true));
        $template->setZipArchive($this->boolParam($request, 'zipArchive', false));
        $template->setImportTranslations($this->boolParam($request, 'importTranslations', false));

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
            'source' => $t->getSource(),
            'sourceUrl' => $t->getSourceUrl(),
            'fileFormat' => $t->getFileFormat(),
            'keyField' => $t->getKeyField(),
            'firstRowHeaders' => $t->isFirstRowHeaders(),
            'zipArchive' => $t->isZipArchive(),
            'importTranslations' => $t->isImportTranslations(),
            'originalFilename' => $t->getOriginalFilename(),
            'createdAt' => $t->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
