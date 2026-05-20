<?php

namespace App\Service;

use App\Entity\TwoFactorCode;
use App\Entity\User;
use App\Repository\TwoFactorCodeRepository;
use Doctrine\ORM\EntityManagerInterface;

class TwoFactorService
{
    private const CODE_TTL_MINUTES = 10;
    private const MAX_ATTEMPTS = 5;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private TwoFactorCodeRepository $codeRepository,
        private TwoFactorEmailSender $emailSender
    ) {}

    public function startChallenge(User $user, string $purpose): TwoFactorCode
    {
        $this->codeRepository->markPreviousUnusedAsUsed($user, $purpose);

        $plainCode = (string) random_int(100000, 999999);
        $challenge = (new TwoFactorCode())
            ->setUser($user)
            ->setPurpose($purpose)
            ->setChallengeId(bin2hex(random_bytes(32)))
            ->setCodeHash($this->hashCode($plainCode))
            ->setExpiresAt(new \DateTime('+'.self::CODE_TTL_MINUTES.' minutes'));

        $this->entityManager->persist($challenge);
        $this->entityManager->flush();

        $this->emailSender->sendCode($user, $plainCode, $purpose);

        return $challenge;
    }

    public function verifyChallenge(string $challengeId, string $code, string $purpose): ?User
    {
        $challenge = $this->codeRepository->findActiveChallenge($challengeId, $purpose);
        if (!$challenge) {
            return null;
        }

        if ($challenge->getExpiresAt() < new \DateTime() || $challenge->getFailedAttempts() >= self::MAX_ATTEMPTS) {
            $challenge->setUsedAt(new \DateTime());
            $this->entityManager->flush();

            return null;
        }

        if (!hash_equals((string) $challenge->getCodeHash(), $this->hashCode($code))) {
            $challenge->incrementFailedAttempts();
            $this->entityManager->flush();

            return null;
        }

        $challenge->setUsedAt(new \DateTime());
        $this->entityManager->flush();

        return $challenge->getUser();
    }

    private function hashCode(string $code): string
    {
        return hash('sha256', trim($code));
    }
}
