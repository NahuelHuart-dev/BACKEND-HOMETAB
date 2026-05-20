<?php

namespace App\Tests\Support;

use App\Entity\Household;
use App\Entity\HouseholdMember;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function createUser(
        string $email,
        string $password = 'HomeTab2026!Test',
        array $roles = ['ROLE_USER'],
        bool $active = true,
    ): User {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user
            ->setEmail($email)
            ->setRoles($roles)
            ->setFirstName('Test')
            ->setLastName('User')
            ->setJoinedAt(new \DateTime('-1 day'))
            ->setIsActive($active)
            ->setTwoFactorEnabled(false)
            ->setAvatarIcon('pi-user');
        $user->setPassword($hasher->hashPassword($user, $password));

        $this->entityManager->persist($user);

        return $user;
    }

    protected function createHousehold(string $name = 'Casa Test', ?string $inviteCode = null): Household
    {
        $household = new Household();
        $household
            ->setName($name)
            ->setInviteCode($inviteCode ?? strtoupper(substr(uniqid('HT'), -6)))
            ->setAvatarIcon('pi-home');

        $this->entityManager->persist($household);

        return $household;
    }

    protected function addMember(User $user, Household $household, string $role = 'member', int $sortOrder = 1): HouseholdMember
    {
        $membership = new HouseholdMember();
        $membership
            ->setUser($user)
            ->setHousehold($household)
            ->setRole($role)
            ->setSortOrder($sortOrder);

        if (!$user->getHouseholdMemberships()->contains($membership)) {
            $user->getHouseholdMemberships()->add($membership);
        }
        if (!$household->getMembers()->contains($membership)) {
            $household->getMembers()->add($membership);
        }

        $this->entityManager->persist($membership);

        return $membership;
    }

    protected function flush(): void
    {
        $this->entityManager->flush();
    }

    protected function authHeaders(User $user): array
    {
        $jwtManager = static::getContainer()->get(JWTTokenManagerInterface::class);

        return [
            'HTTP_AUTHORIZATION' => 'Bearer '.$jwtManager->create($user),
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ];
    }

    protected function json(string $method, string $uri, array $payload = [], array $server = []): void
    {
        $this->client->request(
            $method,
            $uri,
            [],
            [],
            array_merge(['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'], $server),
            json_encode($payload, JSON_THROW_ON_ERROR)
        );
    }

    protected function responseJson(): array
    {
        return json_decode($this->client->getResponse()->getContent() ?: '[]', true, 512, JSON_THROW_ON_ERROR);
    }
}
