<?php

namespace App\Tests\Functional;

use App\Entity\Notification;
use App\Entity\Task;
use App\Tests\Support\ApiTestCase;

class NotificationApiTest extends ApiTestCase
{
    public function testNotificationsAreGroupedByHousehold(): void
    {
        [$user, $household] = $this->notificationScenario('notifications.index@example.test');

        $this->client->request('GET', '/api/notifications', [], [], $this->authHeaders($user));

        self::assertResponseIsSuccessful();
        $payload = $this->responseJson();
        self::assertSame(1, $payload['totalUnread']);
        self::assertSame($household->getId(), $payload['households'][0]['id']);
        self::assertSame('Tarea crítica', $payload['households'][0]['items'][0]['title']);
    }

    public function testNotificationCanBeMarkedAsRead(): void
    {
        [$user] = $this->notificationScenario('notifications.read@example.test');
        $notification = $this->firstSyncedNotification($user);

        $this->json('POST', sprintf('/api/notifications/%d/read', $notification->getId()), [], $this->authHeaders($user));

        self::assertResponseIsSuccessful();
        $reloaded = $this->entityManager->getRepository(Notification::class)->find($notification->getId());
        self::assertInstanceOf(Notification::class, $reloaded);
        self::assertTrue($reloaded->isRead());
    }

    public function testAllNotificationsCanBeMarkedAsRead(): void
    {
        [$user] = $this->notificationScenario('notifications.all@example.test');
        $this->firstSyncedNotification($user);

        $this->json('POST', '/api/notifications/read-all', [], $this->authHeaders($user));

        self::assertResponseIsSuccessful();
        $this->client->request('GET', '/api/notifications', [], [], $this->authHeaders($user));
        self::assertSame(0, $this->responseJson()['totalUnread']);
    }

    public function testUserCannotMarkAnotherUsersNotificationAsRead(): void
    {
        [$owner] = $this->notificationScenario('notifications.foreign.owner@example.test');
        $notification = $this->firstSyncedNotification($owner);

        $intruder = $this->createUser('notifications.foreign.intruder@example.test');
        $this->flush();

        $this->json('POST', sprintf('/api/notifications/%d/read', $notification->getId()), [], $this->authHeaders($intruder));

        self::assertResponseStatusCodeSame(404);
    }

    private function notificationScenario(string $email): array
    {
        $user = $this->createUser($email);
        $household = $this->createHousehold('Casa '.$email);
        $this->addMember($user, $household, 'owner');

        $task = (new Task())
            ->setTitle('Tarea crítica')
            ->setDescription('Debe generar notificación')
            ->setPriority('Alta')
            ->setCompleted(false)
            ->setCreatedAt(new \DateTime())
            ->setDueDate(new \DateTime('+1 day'))
            ->setAssignedTo($user);
        $household->addTask($task);
        $this->entityManager->persist($task);
        $this->flush();

        return [$user, $household, $task];
    }

    private function firstSyncedNotification($user): Notification
    {
        $this->client->request('GET', '/api/notifications', [], [], $this->authHeaders($user));
        self::assertResponseIsSuccessful();

        $notification = $this->entityManager->getRepository(Notification::class)->findOneBy(['user' => $user, 'isActive' => true]);
        self::assertInstanceOf(Notification::class, $notification);

        return $notification;
    }
}
