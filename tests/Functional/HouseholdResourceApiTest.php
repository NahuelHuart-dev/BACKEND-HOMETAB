<?php

namespace App\Tests\Functional;

use App\Tests\Support\ApiTestCase;

class HouseholdResourceApiTest extends ApiTestCase
{
    public function testTaskLifecycleWithinHousehold(): void
    {
        [$user, $household] = $this->memberScenario('tasks.lifecycle@example.test');

        $this->json('POST', sprintf('/api/households/%d/tasks', $household->getId()), [
            'title' => 'Fregar cocina',
            'description' => 'Despues de cenar',
            'priority' => 'Alta',
            'dueDate' => '2026-05-20',
            'assignedTo' => $user->getId(),
        ], $this->authHeaders($user));

        self::assertResponseStatusCodeSame(201);
        $task = $this->responseJson()['task'];
        self::assertSame('Fregar cocina', $task['title']);
        self::assertFalse($task['completed']);

        $this->json('PUT', sprintf('/api/households/%d/tasks/%d', $household->getId(), $task['id']), [
            'completed' => true,
        ], $this->authHeaders($user));

        self::assertResponseIsSuccessful();
        self::assertTrue($this->responseJson()['task']['completed']);

        $this->client->request('DELETE', sprintf('/api/households/%d/tasks/%d', $household->getId(), $task['id']), [], [], $this->authHeaders($user));
        self::assertResponseIsSuccessful();
    }

    public function testTaskRequiresTitle(): void
    {
        [$user, $household] = $this->memberScenario('tasks.validation@example.test');

        $this->json('POST', sprintf('/api/households/%d/tasks', $household->getId()), [
            'description' => 'Sin titulo',
        ], $this->authHeaders($user));

        self::assertResponseStatusCodeSame(400);
    }

    public function testSharedExpenseCreatesEqualShares(): void
    {
        [$user, $household, $mate] = $this->memberScenario('expenses.shared@example.test', withMate: true);

        $this->json('POST', sprintf('/api/households/%d/expenses', $household->getId()), [
            'title' => 'Internet',
            'amount' => 60,
            'category' => 'Facturas',
            'paymentType' => 'shared',
            'paidAt' => '2026-05-18',
            'splitBetween' => [$user->getId(), $mate->getId()],
            'sharePayments' => [
                ['userId' => $user->getId(), 'isPaid' => true],
                ['userId' => $mate->getId(), 'isPaid' => false],
            ],
        ], $this->authHeaders($user));

        self::assertResponseStatusCodeSame(201);
        $expense = $this->responseJson()['expense'];

        self::assertSame('Internet', $expense['title']);
        self::assertSame('shared', $expense['paymentType']);
        self::assertCount(2, $expense['shares']);
        self::assertSame(30.0, (float) $expense['amountPerPerson']);
        self::assertFalse($expense['allParticipantsPaid']);
    }

    public function testExpenseRejectsForeignParticipant(): void
    {
        [$user, $household] = $this->memberScenario('expenses.foreign@example.test');
        $foreign = $this->createUser('expenses.foreign.mate@example.test');
        $this->flush();

        $this->json('POST', sprintf('/api/households/%d/expenses', $household->getId()), [
            'title' => 'Compra',
            'amount' => 20,
            'paymentType' => 'shared',
            'paidAt' => '2026-05-18',
            'splitBetween' => [$foreign->getId()],
        ], $this->authHeaders($user));

        self::assertResponseStatusCodeSame(400);
    }

    public function testEventCanIncludeHouseholdParticipants(): void
    {
        [$user, $household, $mate] = $this->memberScenario('events.create@example.test', withMate: true);

        $this->json('POST', sprintf('/api/households/%d/events', $household->getId()), [
            'title' => 'Cena de piso',
            'description' => 'Organizar menu',
            'startDate' => '2026-05-20T20:00:00',
            'location' => 'Salon',
            'participants' => [$mate->getId()],
        ], $this->authHeaders($user));

        self::assertResponseStatusCodeSame(201);
        $event = $this->responseJson()['event'];

        self::assertSame('Cena de piso', $event['title']);
        self::assertSame('Salon', $event['location']);
        self::assertCount(1, $event['participants']);
        self::assertSame($mate->getId(), $event['participants'][0]['id']);
    }

    public function testProtectedResourcesRejectForeignHousehold(): void
    {
        $user = $this->createUser('resources.deny@example.test');
        $foreign = $this->createHousehold('Casa cerrada', 'RES'.random_int(100, 999));
        $this->flush();

        $this->client->request('GET', sprintf('/api/households/%d/tasks', $foreign->getId()), [], [], $this->authHeaders($user));
        self::assertResponseStatusCodeSame(403);

        $this->client->request('GET', sprintf('/api/households/%d/expenses', $foreign->getId()), [], [], $this->authHeaders($user));
        self::assertResponseStatusCodeSame(403);

        $this->client->request('GET', sprintf('/api/households/%d/events', $foreign->getId()), [], [], $this->authHeaders($user));
        self::assertResponseStatusCodeSame(403);
    }

    private function memberScenario(string $email, bool $withMate = false): array
    {
        $user = $this->createUser($email);
        $household = $this->createHousehold('Casa '.$email, strtoupper(substr(md5($email), 0, 6)));
        $this->addMember($user, $household, 'owner');

        $mate = null;
        if ($withMate) {
            $mate = $this->createUser('mate.'.$email);
            $this->addMember($mate, $household, 'member', 2);
        }

        $this->flush();

        return $withMate ? [$user, $household, $mate] : [$user, $household];
    }
}
