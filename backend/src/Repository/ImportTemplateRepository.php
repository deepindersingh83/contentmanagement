<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ImportTemplate;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ImportTemplate>
 */
class ImportTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImportTemplate::class);
    }

    /**
     * @return ImportTemplate[]
     */
    public function findByOwner(User $owner): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Scheduled templates whose next run is due (or never run).
     *
     * @return ImportTemplate[]
     */
    public function findDue(\DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere("t.scheduleFrequency != 'manual'")
            ->andWhere('t.nextRunAt IS NULL OR t.nextRunAt <= :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }
}
