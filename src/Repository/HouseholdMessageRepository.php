<?php

namespace App\Repository;

use App\Entity\Household;
use App\Entity\HouseholdMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HouseholdMessage>
 */
class HouseholdMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HouseholdMessage::class);
    }

    /**
     * @return HouseholdMessage[]
     */
    public function findRecentForHousehold(Household $household, int $limit = 50, ?int $beforeId = null): array
    {
        $limit = max(1, min($limit, 100));

        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.sender', 's')->addSelect('s')
            ->andWhere('m.household = :household')
            ->andWhere('m.isActive = true')
            ->setParameter('household', $household)
            ->orderBy('m.id', 'DESC')
            ->setMaxResults($limit);

        if ($beforeId !== null) {
            $qb->andWhere('m.id < :beforeId')
                ->setParameter('beforeId', $beforeId);
        }

        return array_reverse($qb->getQuery()->getResult());
    }

    /**
     * @return HouseholdMessage[]
     */
    public function findAfterId(Household $household, int $afterId, int $limit = 100): array
    {
        $limit = max(1, min($limit, 100));

        return $this->createQueryBuilder('m')
            ->leftJoin('m.sender', 's')->addSelect('s')
            ->andWhere('m.household = :household')
            ->andWhere('m.isActive = true')
            ->andWhere('m.id > :afterId')
            ->setParameter('household', $household)
            ->setParameter('afterId', $afterId)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countActiveForHousehold(Household $household): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.household = :household')
            ->andWhere('m.isActive = true')
            ->setParameter('household', $household)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function lastActivityForHousehold(Household $household): null|string|\DateTimeInterface
    {
        return $this->createQueryBuilder('m')
            ->select('MAX(m.createdAt)')
            ->andWhere('m.household = :household')
            ->andWhere('m.isActive = true')
            ->setParameter('household', $household)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<int, array{id: int|null, name: string, email: string|null}>
     */
    public function participantsForHousehold(Household $household): array
    {
        $rows = $this->createQueryBuilder('m')
            ->select('DISTINCT s.id, s.firstName, s.lastName, s.email')
            ->innerJoin('m.sender', 's')
            ->andWhere('m.household = :household')
            ->andWhere('m.isActive = true')
            ->setParameter('household', $household)
            ->orderBy('s.firstName', 'ASC')
            ->addOrderBy('s.lastName', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): array => [
            'id' => $row['id'] ?? null,
            'name' => trim(($row['firstName'] ?? '').' '.($row['lastName'] ?? '')),
            'email' => $row['email'] ?? null,
        ], $rows);
    }
}
