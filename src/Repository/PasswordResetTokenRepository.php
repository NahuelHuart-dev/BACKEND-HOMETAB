<?php

namespace App\Repository;

use App\Entity\PasswordResetToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PasswordResetToken>
 */
class PasswordResetTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetToken::class);
    }

    public function findUsableToken(string $plainToken): ?PasswordResetToken
    {
        return $this->createQueryBuilder('token')
            ->andWhere('token.tokenHash = :hash')
            ->andWhere('token.usedAt IS NULL')
            ->andWhere('token.expiresAt > :now')
            ->setParameter('hash', hash('sha256', $plainToken))
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }
}
