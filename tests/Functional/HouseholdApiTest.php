<?php

namespace App\Tests\Functional;

use App\Tests\Support\ApiTestCase;

class HouseholdApiTest extends ApiTestCase
{
    public function testUserListsOnlyTheirHouseholds(): void
    {
        $user = $this->createUser('households.list@example.test');
        $mine = $this->createHousehold('Casa visible', 'VIS'.random_int(100, 999));
        $foreign = $this->createHousehold('Casa ajena', 'FOR'.random_int(100, 999));
        $this->addMember($user, $mine, 'owner');
        $this->flush();

        $this->client->request('GET', '/api/households', [], [], $this->authHeaders($user));

        self::assertResponseIsSuccessful();
        $names = array_column($this->responseJson(), 'name');

        self::assertContains($mine->getName(), $names);
        self::assertNotContains($foreign->getName(), $names);
    }

    public function testCreateHouseholdMakesCurrentUserOwner(): void
    {
        $user = $this->createUser('households.create@example.test');
        $this->flush();

        $this->json('POST', '/api/households/new', [
            'name' => 'Nuevo Tab',
            'avatarIcon' => 'pi-star',
        ], $this->authHeaders($user));

        self::assertResponseStatusCodeSame(201);
        $data = $this->responseJson();

        self::assertSame('Nuevo Tab', $data['household']['name']);
        self::assertSame('owner', $data['household']['role']);
        self::assertNotEmpty($data['household']['inviteCode']);
    }

    public function testJoinHouseholdByInviteCode(): void
    {
        $user = $this->createUser('households.join@example.test');
        $household = $this->createHousehold('Casa con codigo', 'JOIN'.random_int(10, 99));
        $this->flush();

        $this->json('POST', '/api/households/join', [
            'code' => $household->getInviteCode(),
        ], $this->authHeaders($user));

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Te has unido', $this->responseJson()['message']);
    }

    public function testDashboardRejectsForeignHousehold(): void
    {
        $user = $this->createUser('households.deny@example.test');
        $foreign = $this->createHousehold('Casa privada', 'DEN'.random_int(100, 999));
        $this->flush();

        $this->client->request('GET', sprintf('/api/households/%d/dashboard', $foreign->getId()), [], [], $this->authHeaders($user));

        self::assertResponseStatusCodeSame(403);
    }
}
