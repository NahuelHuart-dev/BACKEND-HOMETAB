<?php

namespace App\Service\Assistant;

use App\Entity\User;

class AssistantContextBuilder
{
    public function buildForUser(User $user): array
    {
        $context = [
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getFullName(),
                'email' => $user->getEmail()
            ],
            'households' => []
        ];

        foreach ($user->getHouseholdMemberships() as $membership) {

            $household = $membership->getHousehold();

            $context['households'][] = [
                'id' => $household->getId(),
                'name' => $household->getName()
            ];
        }

        return $context;
    }
}
