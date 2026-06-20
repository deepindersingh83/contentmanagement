<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Category;
use App\Entity\User;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Route('/api/categories')]
class CategoryController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CategoryRepository $categories,
    ) {
    }

    #[Route('', name: 'api_categories_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json(array_map(fn (Category $c) => [
            'id' => $c->getId(),
            'name' => $c->getName(),
            'slug' => $c->getSlug(),
            'parentId' => $c->getParent()?->getId(),
        ], $this->categories->findAllOrdered()));
    }

    #[Route('', name: 'api_categories_create', methods: ['POST'])]
    public function create(#[CurrentUser] ?User $user, Request $request): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        /** @var array<string, mixed> $data */
        $data = json_decode($request->getContent() ?: '{}', true) ?? [];
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            return $this->json(['message' => 'Category name is required.'], 422);
        }

        $slug = (new AsciiSlugger())->slug($name)->lower()->toString();
        if ($this->categories->findOneBySlug($slug) !== null) {
            return $this->json(['message' => 'That category already exists.'], 422);
        }

        $category = new Category();
        $category->setName($name);
        $category->setSlug($slug);
        if (!empty($data['parentId'])) {
            $category->setParent($this->categories->find((int) $data['parentId']));
        }

        $this->em->persist($category);
        $this->em->flush();

        return $this->json(['id' => $category->getId(), 'name' => $category->getName(), 'slug' => $category->getSlug()], 201);
    }

    #[Route('/{id}', name: 'api_categories_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(#[CurrentUser] ?User $user, Category $category): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $this->em->remove($category);
        $this->em->flush();

        return $this->json(null, 204);
    }
}
