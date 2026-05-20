<?php

namespace App\Service;

use App\Entity\Household;
use App\Entity\User;
use App\Repository\HouseholdRepository;
use Symfony\Bundle\SecurityBundle\Security;

/** Centraliza la comprobacion de acceso a una casa para la API. */
class HouseholdAccessService
{
    public function __construct(
        private readonly HouseholdRepository $householdRepository,
        private readonly Security $security,
    ) {
    }

    public function getAccessibleHousehold(int $homeId, User $user): ?Household
    {
        $household = $this->householdRepository->find($homeId);
        if (!$household) {
            return null;
        }

        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return $household;
        }

        return $this->userBelongsToHousehold($user, $household) ? $household : null;
    }

    public function getMemberHousehold(int $homeId, User $user): ?Household
    {
        $household = $this->householdRepository->find($homeId);
        if (!$household) {
            return null;
        }

        return $this->userBelongsToHousehold($user, $household) ? $household : null;
    }

    /**
     * @return Household[]
     */
    public function getMemberHouseholds(User $user): array
    {
        $households = [];
        foreach ($user->getHouseholdMemberships() as $membership) {
            $household = $membership->getHousehold();
            if ($household) {
                $households[$household->getId()] = $household;
            }
        }

        uasort($households, static fn (Household $a, Household $b): int => strcasecmp((string) $a->getName(), (string) $b->getName()));

        return array_values($households);
    }

    /**
     * @return User[]
     */
    public function getHouseholdUsers(Household $household): array
    {
        $users = [];
        foreach ($household->getMembers() as $membership) {
            $user = $membership->getUser();
            if ($user) {
                $users[] = $user;
            }
        }

        return $users;
    }

    public function userBelongsToHousehold(User $user, Household $household): bool
    {
        foreach ($user->getHouseholdMemberships() as $membership) {
            if ($membership->getHousehold()?->getId() === $household->getId()) {
                return true;
            }
        }

        return false;
    }
}
