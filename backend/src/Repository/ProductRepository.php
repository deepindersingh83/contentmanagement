<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findOneBySku(string $sku): ?Product
    {
        return $this->findOneBy(['sku' => $sku]);
    }

    /**
     * @return array{items: Product[], total: int}
     */
    public function search(?string $q, int $page, int $perPage): array
    {
        $qb = $this->createQueryBuilder('p')->orderBy('p.updatedAt', 'DESC');

        if ($q !== null && $q !== '') {
            $qb->andWhere('p.title LIKE :q OR p.sku LIKE :q OR p.gtin LIKE :q')
                ->setParameter('q', '%'.$q.'%');
        }

        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(p.id)')->orderBy('p.id', 'ASC')->getQuery()->getSingleScalarResult();

        $items = $qb
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return ['items' => $items, 'total' => $total];
    }
}
