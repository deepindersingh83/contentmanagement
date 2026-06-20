<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Brand;
use App\Entity\User;
use App\Repository\BrandRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Route('/api/brands')]
class BrandController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly BrandRepository $brands,
    ) {
    }

    #[Route('', name: 'api_brands_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json(array_map(fn (Brand $b) => [
            'id' => $b->getId(),
            'name' => $b->getName(),
            'slug' => $b->getSlug(),
        ], $this->brands->findAllOrdered()));
    }

    #[Route('', name: 'api_brands_create', methods: ['POST'])]
    public function create(#[CurrentUser] ?User $user, Request $request): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        /** @var array<string, mixed> $data */
        $data = json_decode($request->getContent() ?: '{}', true) ?? [];
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            return $this->json(['message' => 'Brand name is required.'], 422);
        }

        $slug = (new AsciiSlugger())->slug($name)->lower()->toString();
        if ($this->brands->findOneBySlug($slug) !== null) {
            return $this->json(['message' => 'That brand already exists.'], 422);
        }

        $brand = new Brand();
        $brand->setName($name);
        $brand->setSlug($slug);

        $this->em->persist($brand);
        $this->em->flush();

        return $this->json(['id' => $brand->getId(), 'name' => $brand->getName(), 'slug' => $brand->getSlug()], 201);
    }

    #[Route('/{id}', name: 'api_brands_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(#[CurrentUser] ?User $user, Brand $brand): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $this->em->remove($brand);
        $this->em->flush();

        return $this->json(null, 204);
    }
}
