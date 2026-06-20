<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ImportRun;
use App\Repository\ImportRunRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/import-runs')]
class ImportRunController extends AbstractController
{
    public function __construct(private readonly ImportRunRepository $runs)
    {
    }

    #[Route('', name: 'api_import_runs_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $limit = min(100, max(1, (int) $request->query->get('limit', '30')));

        return $this->json(array_map(fn (ImportRun $r) => [
            'id' => $r->getId(),
            'template' => $r->getTemplate() ? ['id' => $r->getTemplate()->getId(), 'name' => $r->getTemplate()->getName()] : null,
            'supplier' => $r->getSupplier() ? ['id' => $r->getSupplier()->getId(), 'name' => $r->getSupplier()->getName()] : null,
            'status' => $r->getStatus(),
            'rowsTotal' => $r->getRowsTotal(),
            'rowsCreated' => $r->getRowsCreated(),
            'rowsUpdated' => $r->getRowsUpdated(),
            'rowsMatched' => $r->getRowsMatched(),
            'rowsFailed' => $r->getRowsFailed(),
            'startedAt' => $r->getStartedAt()->format(\DateTimeInterface::ATOM),
            'finishedAt' => $r->getFinishedAt()?->format(\DateTimeInterface::ATOM),
        ], $this->runs->findRecent($limit)));
    }
}
