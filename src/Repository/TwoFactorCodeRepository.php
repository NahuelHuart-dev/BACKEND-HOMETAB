<?php

namespace App\Repository;

use App\Entity\TwoFactorCode;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TwoFactorCode>
 */
class TwoFactorCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TwoFactorCode::class);
    }

    public function findActiveChallenge(string $challengeId, string $purpose): ?TwoFactorCode
    {
        return $this->createQueryBuilder('code')
            ->andWhere('code.challengeId = :challengeId')
            ->andWhere('code.purpose = :purpose')
            ->andWhere('code.usedAt IS NULL')
            ->setParameter('challengeId', $challengeId)
            ->setParameter('purpose', $purpose)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function markPreviousUnusedAsUsed(User $user, string $purpose): void
    {
        $this->createQueryBuilder('code')
            ->update()
            ->set('code.usedAt', ':now')
            ->andWhere('code.user = :user')
            ->andWhere('code.purpose = :purpose')
            ->andWhere('code.usedAt IS NULL')
            ->setParameter('now', new \DateTime())
            ->setParameter('user', $user)
            ->setParameter('purpose', $purpose)
            ->getQuery()
            ->execute();
    }
}
