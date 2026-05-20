<?php

namespace App\Tests\Functional;

use App\Entity\HouseholdMessage;
use App\Tests\Support\ApiTestCase;

class HouseholdChatApiTest extends ApiTestCase
{
    public function testMemberCanListAndCreateMessages(): void
    {
        [$user, $household] = $this->chatScenario('chat.create@example.test');
        $message = $this->createMessage($user, $household, 'Mensaje inicial');
        $this->flush();

        $this->client->request('GET', sprintf('/api/households/%d/chat/messages', $household->getId()), [], [], $this->authHeaders($user));
        self::assertResponseIsSuccessful();
        self::assertSame('Mensaje inicial', $this->responseJson()[0]['content']);

        $this->json('POST', sprintf('/api/households/%d/chat/messages', $household->getId()), [
            'content' => 'Nuevo mensaje',
        ], $this->authHeaders($user));

        self::assertResponseStatusCodeSame(201);
        self::assertSame('Nuevo mensaje', $this->responseJson()['chatMessage']['content']);
        self::assertNotSame($message->getId(), $this->responseJson()['chatMessage']['id']);
    }

    public function testChatRejectsEmptyMessage(): void
    {
        [$user, $household] = $this->chatScenario('chat.empty@example.test');
        $this->flush();

        $this->json('POST', sprintf('/api/households/%d/chat/messages', $household->getId()), [
            'content' => '   ',
        ], $this->authHeaders($user));

        self::assertResponseStatusCodeSame(400);
    }

    public function testOnlySenderCanEditOrDeleteMessage(): void
    {
        [$owner, $household, $mate] = $this->chatScenario('chat.permissions@example.test', withMate: true);
        $message = $this->createMessage($owner, $household, 'Mensaje privado');
        $this->flush();

        $this->json('PATCH', sprintf('/api/households/%d/chat/messages/%d', $household->getId(), $message->getId()), [
            'content' => 'Intento de edicion',
        ], $this->authHeaders($mate));
        self::assertResponseStatusCodeSame(403);

        $this->client->request('DELETE', sprintf('/api/households/%d/chat/messages/%d', $household->getId(), $message->getId()), [], [], $this->authHeaders($mate));
        self::assertResponseStatusCodeSame(403);

        $this->json('PATCH', sprintf('/api/households/%d/chat/messages/%d', $household->getId(), $message->getId()), [
            'content' => 'Editado por autor',
        ], $this->authHeaders($owner));
        self::assertResponseIsSuccessful();
        self::assertSame('Editado por autor', $this->responseJson()['chatMessage']['content']);
    }

    public function testChatRejectsForeignHousehold(): void
    {
        $user = $this->createUser('chat.foreign@example.test');
        $foreign = $this->createHousehold('Casa chat ajena');
        $this->flush();

        $this->client->request('GET', sprintf('/api/households/%d/chat/messages', $foreign->getId()), [], [], $this->authHeaders($user));

        self::assertResponseStatusCodeSame(403);
    }

    private function chatScenario(string $email, bool $withMate = false): array
    {
        $user = $this->createUser($email);
        $household = $this->createHousehold('Casa '.$email);
        $this->addMember($user, $household, 'owner');

        $mate = null;
        if ($withMate) {
            $mate = $this->createUser('mate.'.$email);
            $this->addMember($mate, $household, 'member', 2);
        }

        return $withMate ? [$user, $household, $mate] : [$user, $household];
    }

    private function createMessage($sender, $household, string $content): HouseholdMessage
    {
        $message = (new HouseholdMessage())
            ->setSender($sender)
            ->setHousehold($household)
            ->setContent($content);

        $this->entityManager->persist($message);

        return $message;
    }
}
