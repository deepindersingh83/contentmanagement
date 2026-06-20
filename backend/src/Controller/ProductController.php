<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Repository\BrandRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/products')]
class ProductController extends AbstractController
{
    private const STATUSES = ['active', 'draft', 'archived'];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProductRepository $products,
        private readonly CategoryRepository $categories,
        private readonly BrandRepository $brands,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'api_products_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $q = $request->query->get('q');
        $page = max(1, (int) $request->query->get('page', '1'));
        $perPage = min(100, max(1, (int) $request->query->get('perPage', '25')));

        $result = $this->products->search($q !== null ? (string) $q : null, $page, $perPage);

        return $this->json([
            'items' => array_map(fn (Product $p) => $this->serialize($p), $result['items']),
            'total' => $result['total'],
            'page' => $page,
            'perPage' => $perPage,
        ]);
    }

    #[Route('/{id}', name: 'api_products_get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(Product $product): JsonResponse
    {
        return $this->json($this->serialize($product));
    }

    #[Route('', name: 'api_products_create', methods: ['POST'])]
    public function create(#[CurrentUser] ?User $user, Request $request): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        return $this->save(new Product(), $request, 201);
    }

    #[Route('/{id}', name: 'api_products_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(#[CurrentUser] ?User $user, Product $product, Request $request): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        return $this->save($product, $request, 200);
    }

    #[Route('/{id}', name: 'api_products_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(#[CurrentUser] ?User $user, Product $product): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $this->em->remove($product);
        $this->em->flush();

        return $this->json(null, 204);
    }

    private function save(Product $product, Request $request, int $status): JsonResponse
    {
        /** @var array<string, mixed> $data */
        $data = json_decode($request->getContent() ?: '{}', true) ?? [];

        if (array_key_exists('sku', $data)) {
            $product->setSku(trim((string) $data['sku']));
        }
        if (array_key_exists('title', $data)) {
            $product->setTitle(trim((string) $data['title']));
        }
        if (array_key_exists('gtin', $data)) {
            $product->setGtin($this->nullable($data['gtin']));
        }
        if (array_key_exists('shortDescription', $data)) {
            $product->setShortDescription($this->nullable($data['shortDescription']));
        }
        if (array_key_exists('longDescription', $data)) {
            $product->setLongDescription($this->nullable($data['longDescription']));
        }
        if (array_key_exists('weightGrams', $data)) {
            $product->setWeightGrams($this->nullable($data['weightGrams']));
        }
        if (array_key_exists('sellPrice', $data)) {
            $product->setSellPrice($this->nullable($data['sellPrice']));
        }
        if (array_key_exists('primaryImageUrl', $data)) {
            $product->setPrimaryImageUrl($this->nullable($data['primaryImageUrl']));
        }
        if (array_key_exists('status', $data) && in_array($data['status'], self::STATUSES, true)) {
            $product->setStatus((string) $data['status']);
        }
        if (array_key_exists('categoryId', $data)) {
            $product->setCategory(!empty($data['categoryId']) ? $this->categories->find((int) $data['categoryId']) : null);
        }
        if (array_key_exists('brandId', $data)) {
            $product->setBrand(!empty($data['brandId']) ? $this->brands->find((int) $data['brandId']) : null);
        }

        $errors = $this->validator->validate($product);
        if (count($errors) > 0) {
            return $this->json(['message' => (string) $errors->get(0)->getMessage()], 422);
        }

        $existing = $this->products->findOneBySku($product->getSku());
        if ($existing !== null && $existing->getId() !== $product->getId()) {
            return $this->json(['message' => 'A product with that SKU already exists.'], 422);
        }

        $this->em->persist($product);
        $this->em->flush();

        return $this->json($this->serialize($product), $status);
    }

    private function nullable(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Product $p): array
    {
        return [
            'id' => $p->getId(),
            'sku' => $p->getSku(),
            'gtin' => $p->getGtin(),
            'title' => $p->getTitle(),
            'shortDescription' => $p->getShortDescription(),
            'longDescription' => $p->getLongDescription(),
            'status' => $p->getStatus(),
            'weightGrams' => $p->getWeightGrams(),
            'sellPrice' => $p->getSellPrice(),
            'primaryImageUrl' => $p->getPrimaryImageUrl(),
            'category' => $p->getCategory() ? ['id' => $p->getCategory()->getId(), 'name' => $p->getCategory()->getName()] : null,
            'brand' => $p->getBrand() ? ['id' => $p->getBrand()->getId(), 'name' => $p->getBrand()->getName()] : null,
            'updatedAt' => $p->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
