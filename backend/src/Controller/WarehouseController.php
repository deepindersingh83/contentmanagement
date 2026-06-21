<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\Warehouse;
use App\Repository\SupplierRepository;
use App\Repository\WarehouseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/warehouses')]
class WarehouseController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly WarehouseRepository $warehouses,
        private readonly SupplierRepository $suppliers,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'api_warehouses_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json(array_map(fn (Warehouse $w) => $this->serialize($w), $this->warehouses->findAllOrdered()));
    }

    #[Route('', name: 'api_warehouses_create', methods: ['POST'])]
    public function create(#[CurrentUser] ?User $user, Request $request): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        return $this->save(new Warehouse(), $request, 201);
    }

    #[Route('/{id}', name: 'api_warehouses_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(#[CurrentUser] ?User $user, Warehouse $warehouse, Request $request): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        return $this->save($warehouse, $request, 200);
    }

    #[Route('/{id}', name: 'api_warehouses_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(#[CurrentUser] ?User $user, Warehouse $warehouse): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $this->em->remove($warehouse);
        $this->em->flush();

        return $this->json(null, 204);
    }

    private function save(Warehouse $warehouse, Request $request, int $status): JsonResponse
    {
        /** @var array<string, mixed> $data */
        $data = json_decode($request->getContent() ?: '{}', true) ?? [];

        if (array_key_exists('name', $data)) {
            $warehouse->setName(trim((string) $data['name']));
        }
        if (array_key_exists('code', $data)) {
            $warehouse->setCode(trim((string) $data['code']));
        }
        if (array_key_exists('country', $data)) {
            $warehouse->setCountry($this->nullable($data['country']));
        }
        if (array_key_exists('region', $data)) {
            $warehouse->setRegion($this->nullable($data['region']));
        }
        if (array_key_exists('postcode', $data)) {
            $warehouse->setPostcode($this->nullable($data['postcode']));
        }
        if (array_key_exists('supplierId', $data)) {
            $warehouse->setSupplier(!empty($data['supplierId']) ? $this->suppliers->find((int) $data['supplierId']) : null);
        }

        $errors = $this->validator->validate($warehouse);
        if (count($errors) > 0) {
            return $this->json(['message' => (string) $errors->get(0)->getMessage()], 422);
        }

        $this->em->persist($warehouse);
        $this->em->flush();

        return $this->json($this->serialize($warehouse), $status);
    }

    private function nullable(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Warehouse $w): array
    {
        return [
            'id' => $w->getId(),
            'code' => $w->getCode(),
            'name' => $w->getName(),
            'country' => $w->getCountry(),
            'region' => $w->getRegion(),
            'postcode' => $w->getPostcode(),
            'supplier' => $w->getSupplier() ? ['id' => $w->getSupplier()->getId(), 'name' => $w->getSupplier()->getName()] : null,
        ];
    }
}
