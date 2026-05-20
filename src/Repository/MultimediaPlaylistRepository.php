<?php

namespace App\Repository;

use App\Entity\Household;
use App\Entity\MultimediaPlaylist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<MultimediaPlaylist> */
class MultimediaPlaylistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MultimediaPlaylist::class);
    }

    /** @return MultimediaPlaylist[] */
    public function findForHousehold(Household $household): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.createdBy', 'u')->addSelect('u')
            ->leftJoin('p.videos', 'v')->addSelect('v')
            ->leftJoin('v.addedBy', 'vu')->addSelect('vu')
            ->andWhere('p.household = :household')
            ->setParameter('household', $household)
            ->orderBy('p.createdAt', 'DESC')
            ->addOrderBy('v.position', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
