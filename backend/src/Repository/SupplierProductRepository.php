<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SupplierProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SupplierProduct>
 */
class SupplierProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupplierProduct::class);
    }

    public function findOneBySupplierRef(int $supplierId, string $ref): ?SupplierProduct
    {
        return $this->createQueryBuilder('sp')
            ->andWhere('sp.supplier = :sid AND sp.supplierRefCode = :ref')
            ->setParameter('sid', $supplierId)
            ->setParameter('ref', $ref)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array{items: SupplierProduct[], total: int}
     */
    public function search(?int $supplierId, ?int $productId, ?bool $unmatched, ?string $q, int $page, int $perPage): array
    {
        $qb = $this->createQueryBuilder('sp')->orderBy('sp.updatedAt', 'DESC');

        if ($supplierId !== null) {
            $qb->andWhere('sp.supplier = :sid')->setParameter('sid', $supplierId);
        }
        if ($productId !== null) {
            $qb->andWhere('sp.product = :pid')->setParameter('pid', $productId);
        }
        if ($unmatched === true) {
            $qb->andWhere('sp.product IS NULL');
        } elseif ($unmatched === false) {
            $qb->andWhere('sp.product IS NOT NULL');
        }
        if ($q !== null && $q !== '') {
            $qb->andWhere('sp.supplierRefCode LIKE :q OR sp.supplierSku LIKE :q OR sp.title LIKE :q')
                ->setParameter('q', '%'.$q.'%');
        }

        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(sp.id)')->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();

        $items = $qb->setFirstResult(($page - 1) * $perPage)->setMaxResults($perPage)->getQuery()->getResult();

        return ['items' => $items, 'total' => $total];
    }
}
