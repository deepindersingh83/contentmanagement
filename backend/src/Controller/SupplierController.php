<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Supplier;
use App\Entity\User;
use App\Repository\SupplierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/suppliers')]
class SupplierController extends AbstractController
{
    private const WEIGHT_UNITS = ['g', 'kg', 'lb', 'oz'];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SupplierRepository $suppliers,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'api_suppliers_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json(array_map(
            fn (Supplier $s) => $this->serialize($s),
            $this->suppliers->findAllOrdered(),
        ));
    }

    #[Route('', name: 'api_suppliers_create', methods: ['POST'])]
    public function create(#[CurrentUser] ?User $user, Request $request): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $supplier = new Supplier();

        return $this->save($supplier, $request, 201);
    }

    #[Route('/{id}', name: 'api_suppliers_get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(Supplier $supplier): JsonResponse
    {
        return $this->json($this->serialize($supplier));
    }

    #[Route('/{id}', name: 'api_suppliers_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(#[CurrentUser] ?User $user, Supplier $supplier, Request $request): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        return $this->save($supplier, $request, 200);
    }

    #[Route('/{id}', name: 'api_suppliers_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(#[CurrentUser] ?User $user, Supplier $supplier): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $this->em->remove($supplier);
        $this->em->flush();

        return $this->json(null, 204);
    }

    private function save(Supplier $supplier, Request $request, int $status): JsonResponse
    {
        /** @var array<string, mixed> $data */
        $data = json_decode($request->getContent() ?: '{}', true) ?? [];

        if (array_key_exists('name', $data)) {
            $supplier->setName(trim((string) $data['name']));
        }
        if (array_key_exists('code', $data) && trim((string) $data['code']) !== '') {
            $supplier->setCode($this->normaliseCode((string) $data['code']));
        } elseif ($supplier->getCode() === '' && $supplier->getName() !== '') {
            $supplier->setCode($this->normaliseCode($supplier->getName()));
        }
        if (array_key_exists('active', $data)) {
            $supplier->setActive((bool) $data['active']);
        }
        if (array_key_exists('defaultCurrency', $data) && trim((string) $data['defaultCurrency']) !== '') {
            $supplier->setDefaultCurrency(substr(trim((string) $data['defaultCurrency']), 0, 3));
        }
        if (array_key_exists('defaultWeightUnit', $data)) {
            $unit = (string) $data['defaultWeightUnit'];
            if (in_array($unit, self::WEIGHT_UNITS, true)) {
                $supplier->setDefaultWeightUnit($unit);
            }
        }
        if (array_key_exists('website', $data)) {
            $supplier->setWebsite($this->nullable($data['website']));
        }
        if (array_key_exists('contactEmail', $data)) {
            $supplier->setContactEmail($this->nullable($data['contactEmail']));
        }
        if (array_key_exists('notes', $data)) {
            $supplier->setNotes($this->nullable($data['notes']));
        }

        $errors = $this->validator->validate($supplier);
        if (count($errors) > 0) {
            return $this->json(['message' => (string) $errors->get(0)->getMessage()], 422);
        }

        // Enforce unique code.
        $existing = $this->suppliers->findOneByCode($supplier->getCode());
        if ($existing !== null && $existing->getId() !== $supplier->getId()) {
            return $this->json(['message' => 'A supplier with that code already exists.'], 422);
        }

        $this->em->persist($supplier);
        $this->em->flush();

        return $this->json($this->serialize($supplier), $status);
    }

    private function normaliseCode(string $value): string
    {
        $slug = (new AsciiSlugger())->slug($value)->lower()->toString();

        return $slug !== '' ? $slug : 'supplier';
    }

    private function nullable(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Supplier $s): array
    {
        return [
            'id' => $s->getId(),
            'name' => $s->getName(),
            'code' => $s->getCode(),
            'active' => $s->isActive(),
            'defaultCurrency' => $s->getDefaultCurrency(),
            'defaultWeightUnit' => $s->getDefaultWeightUnit(),
            'website' => $s->getWebsite(),
            'contactEmail' => $s->getContactEmail(),
            'notes' => $s->getNotes(),
            'createdAt' => $s->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
