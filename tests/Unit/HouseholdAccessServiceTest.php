<?php

namespace App\Tests\Unit;

use App\Entity\Household;
use App\Entity\HouseholdMember;
use App\Entity\User;
use App\Repository\HouseholdRepository;
use App\Service\HouseholdAccessService;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class HouseholdAccessServiceTest extends TestCase
{
    public function testUserBelongsToHouseholdOnlyMatchesOwnedMemberships(): void
    {
        $user = new User();
        $household = (new Household())->setName('Casa propia');
        $otherHousehold = (new Household())->setName('Casa ajena');
        $this->setEntityId($household, 1);
        $this->setEntityId($otherHousehold, 2);

        $membership = new HouseholdMember();
        $membership->setUser($user)->setHousehold($household);
        $user->getHouseholdMemberships()->add($membership);

        $service = new HouseholdAccessService(
            $this->createMock(HouseholdRepository::class),
            $this->createMock(Security::class),
        );

        self::assertTrue($service->userBelongsToHousehold($user, $household));
        self::assertFalse($service->userBelongsToHousehold($user, $otherHousehold));
    }

    private function setEntityId(object $entity, int $id): void
    {
        $property = new \ReflectionProperty($entity, 'id');
        $property->setValue($entity, $id);
    }
}
