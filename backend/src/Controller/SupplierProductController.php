<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\SupplierProduct;
use App\Entity\User;
use App\Repository\ProductRepository;
use App\Repository\SupplierProductRepository;
use App\Repository\SupplierRepository;
use App\Service\WeightConverter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/supplier-products')]
class SupplierProductController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SupplierProductRepository $offers,
        private readonly SupplierRepository $suppliers,
        private readonly ProductRepository $products,
    ) {
    }

    #[Route('', name: 'api_supplier_products_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $supplierId = $request->query->get('supplierId');
        $productId = $request->query->get('productId');
        $unmatchedParam = $request->query->get('unmatched');
        $unmatched = $unmatchedParam === null ? null : ($unmatchedParam === '1' || $unmatchedParam === 'true');
        $q = $request->query->get('q');
        $page = max(1, (int) $request->query->get('page', '1'));
        $perPage = min(100, max(1, (int) $request->query->get('perPage', '25')));

        $result = $this->offers->search(
            $supplierId !== null && $supplierId !== '' ? (int) $supplierId : null,
            $productId !== null && $productId !== '' ? (int) $productId : null,
            $unmatched,
            $q !== null ? (string) $q : null,
            $page,
            $perPage,
        );

        return $this->json([
            'items' => array_map(fn (SupplierProduct $o) => $this->serialize($o), $result['items']),
            'total' => $result['total'],
            'page' => $page,
            'perPage' => $perPage,
        ]);
    }

    #[Route('/{id}', name: 'api_supplier_products_get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(SupplierProduct $offer): JsonResponse
    {
        return $this->json($this->serialize($offer, true));
    }

    #[Route('', name: 'api_supplier_products_create', methods: ['POST'])]
    public function create(#[CurrentUser] ?User $user, Request $request): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        /** @var array<string, mixed> $data */
        $data = json_decode($request->getContent() ?: '{}', true) ?? [];

        $supplierId = (int) ($data['supplierId'] ?? 0);
        $supplier = $supplierId ? $this->suppliers->find($supplierId) : null;
        if ($supplier === null) {
            return $this->json(['message' => 'A valid supplier is required.'], 422);
        }

        $ref = trim((string) ($data['supplierRefCode'] ?? ''));
        if ($ref === '') {
            return $this->json(['message' => 'Supplier reference code is required.'], 422);
        }
        if ($this->offers->findOneBySupplierRef($supplierId, $ref) !== null) {
            return $this->json(['message' => 'An offer with that reference already exists for this supplier.'], 422);
        }

        $offer = new SupplierProduct();
        $offer->setSupplier($supplier);
        $offer->setSupplierRefCode($ref);
        $offer->setCurrency($supplier->getDefaultCurrency());

        return $this->apply($offer, $data, 201);
    }

    #[Route('/{id}', name: 'api_supplier_products_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(#[CurrentUser] ?User $user, SupplierProduct $offer, Request $request): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        /** @var array<string, mixed> $data */
        $data = json_decode($request->getContent() ?: '{}', true) ?? [];

        return $this->apply($offer, $data, 200);
    }

    /**
     * Link / unlink the offer to a master product, and optionally flag it as the
     * primary source. Setting primary clears the flag on the product's other
     * offers so there is a single source of truth.
     */
    #[Route('/{id}/link', name: 'api_supplier_products_link', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function link(#[CurrentUser] ?User $user, SupplierProduct $offer, Request $request): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        /** @var array<string, mixed> $data */
        $data = json_decode($request->getContent() ?: '{}', true) ?? [];

        if (array_key_exists('productId', $data)) {
            $offer->setProduct(!empty($data['productId']) ? $this->products->find((int) $data['productId']) : null);
        }
        if (array_key_exists('isPrimary', $data)) {
            $isPrimary = (bool) $data['isPrimary'];
            $offer->setIsPrimary($isPrimary);
            if ($isPrimary && $offer->getProduct() !== null) {
                foreach ($this->offers->findBy(['product' => $offer->getProduct()]) as $sibling) {
                    if ($sibling->getId() !== $offer->getId()) {
                        $sibling->setIsPrimary(false);
                    }
                }
            }
        }

        $this->em->flush();

        return $this->json($this->serialize($offer));
    }

    #[Route('/{id}', name: 'api_supplier_products_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(#[CurrentUser] ?User $user, SupplierProduct $offer): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $this->em->remove($offer);
        $this->em->flush();

        return $this->json(null, 204);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function apply(SupplierProduct $offer, array $data, int $status): JsonResponse
    {
        if (array_key_exists('supplierSku', $data)) {
            $offer->setSupplierSku($this->nullable($data['supplierSku']));
        }
        if (array_key_exists('matchKey', $data)) {
            $offer->setMatchKey($this->nullable($data['matchKey']));
        }
        if (array_key_exists('costPrice', $data)) {
            $offer->setCostPrice((string) (is_numeric($data['costPrice']) ? $data['costPrice'] : '0'));
        }
        if (array_key_exists('currency', $data) && trim((string) $data['currency']) !== '') {
            $offer->setCurrency(substr(trim((string) $data['currency']), 0, 3));
        }
        if (array_key_exists('stockQuantity', $data)) {
            $offer->setStockQuantity((int) $data['stockQuantity']);
        }
        if (array_key_exists('weightValue', $data) || array_key_exists('weightUnit', $data)) {
            $offer->setWeightValue($this->nullable($data['weightValue'] ?? $offer->getWeightValue()));
            $offer->setWeightUnit($this->nullable($data['weightUnit'] ?? $offer->getWeightUnit()));
            $offer->setWeightGrams(WeightConverter::toGrams($offer->getWeightValue(), $offer->getWeightUnit()));
        }
        if (array_key_exists('title', $data)) {
            $offer->setTitle($this->nullable($data['title']));
        }
        if (array_key_exists('shortDescription', $data)) {
            $offer->setShortDescription($this->nullable($data['shortDescription']));
        }
        if (array_key_exists('longDescription', $data)) {
            $offer->setLongDescription($this->nullable($data['longDescription']));
        }
        if (array_key_exists('categoryRaw', $data)) {
            $offer->setCategoryRaw($this->nullable($data['categoryRaw']));
        }
        if (array_key_exists('imageUrl', $data)) {
            $offer->setImageUrl($this->nullable($data['imageUrl']));
        }
        if (array_key_exists('leadTimeDays', $data)) {
            $offer->setLeadTimeDays($data['leadTimeDays'] !== null && $data['leadTimeDays'] !== '' ? (int) $data['leadTimeDays'] : null);
        }

        $offer->setLastSeenAt(new \DateTimeImmutable());

        $this->em->persist($offer);
        $this->em->flush();

        return $this->json($this->serialize($offer), $status);
    }

    private function nullable(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(SupplierProduct $o, bool $withStock = false): array
    {
        $data = [
            'id' => $o->getId(),
            'supplier' => $o->getSupplier() ? ['id' => $o->getSupplier()->getId(), 'name' => $o->getSupplier()->getName()] : null,
            'product' => $o->getProduct() ? ['id' => $o->getProduct()->getId(), 'sku' => $o->getProduct()->getSku(), 'title' => $o->getProduct()->getTitle()] : null,
            'supplierRefCode' => $o->getSupplierRefCode(),
            'supplierSku' => $o->getSupplierSku(),
            'matchKey' => $o->getMatchKey(),
            'costPrice' => $o->getCostPrice(),
            'currency' => $o->getCurrency(),
            'stockQuantity' => $o->getStockQuantity(),
            'weightValue' => $o->getWeightValue(),
            'weightUnit' => $o->getWeightUnit(),
            'weightGrams' => $o->getWeightGrams(),
            'title' => $o->getTitle(),
            'categoryRaw' => $o->getCategoryRaw(),
            'imageUrl' => $o->getImageUrl(),
            'leadTimeDays' => $o->getLeadTimeDays(),
            'isPrimary' => $o->isPrimary(),
            'updatedAt' => $o->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];

        if ($withStock) {
            $data['shortDescription'] = $o->getShortDescription();
            $data['longDescription'] = $o->getLongDescription();
            $data['stockLevels'] = array_map(fn ($s) => [
                'id' => $s->getId(),
                'warehouse' => $s->getWarehouse() ? ['id' => $s->getWarehouse()->getId(), 'name' => $s->getWarehouse()->getName()] : null,
                'quantity' => $s->getQuantity(),
            ], $o->getStockLevels()->toArray());
        }

        return $data;
    }
}
