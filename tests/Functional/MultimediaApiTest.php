<?php

namespace App\Tests\Functional;

use App\Tests\Support\ApiTestCase;

class MultimediaApiTest extends ApiTestCase
{
    public function testMemberCanListAndCreatePlaylist(): void
    {
        [$user, $household] = $this->multimediaScenario('multimedia.create@example.test');
        $this->flush();

        $this->client->request('GET', sprintf('/api/households/%d/multimedia/playlists', $household->getId()), [], [], $this->authHeaders($user));
        self::assertResponseIsSuccessful();
        self::assertSame([], $this->responseJson()['playlists']);

        $this->json('POST', sprintf('/api/households/%d/multimedia/playlists', $household->getId()), [
            'name' => 'Recetas',
        ], $this->authHeaders($user));

        self::assertResponseStatusCodeSame(201);
        self::assertSame('Recetas', $this->responseJson()['playlist']['name']);
    }

    public function testPlaylistRequiresValidName(): void
    {
        [$user, $household] = $this->multimediaScenario('multimedia.validation@example.test');
        $this->flush();

        $this->json('POST', sprintf('/api/households/%d/multimedia/playlists', $household->getId()), [
            'name' => '',
        ], $this->authHeaders($user));
        self::assertResponseStatusCodeSame(400);

        $this->json('POST', sprintf('/api/households/%d/multimedia/playlists', $household->getId()), [
            'name' => str_repeat('x', 121),
        ], $this->authHeaders($user));
        self::assertResponseStatusCodeSame(400);
    }

    public function testMemberCanAddYoutubeVideoById(): void
    {
        [$user, $household] = $this->multimediaScenario('multimedia.video@example.test');
        $this->flush();

        $this->json('POST', sprintf('/api/households/%d/multimedia/playlists', $household->getId()), [
            'name' => 'Musica',
        ], $this->authHeaders($user));
        $playlistId = $this->responseJson()['playlist']['id'];

        $this->json('POST', sprintf('/api/households/%d/multimedia/playlists/%d/videos', $household->getId(), $playlistId), [
            'youtubeId' => 'dQw4w9WgXcQ',
            'title' => 'Video de prueba',
            'channelTitle' => 'HomeTab',
        ], $this->authHeaders($user));

        self::assertResponseStatusCodeSame(201);
        $video = $this->responseJson()['video'];
        self::assertSame('dQw4w9WgXcQ', $video['youtubeId']);
        self::assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $video['url']);
    }

    public function testMultimediaRejectsForeignHousehold(): void
    {
        $user = $this->createUser('multimedia.foreign@example.test');
        $foreign = $this->createHousehold('Casa multimedia ajena');
        $this->flush();

        $this->client->request('GET', sprintf('/api/households/%d/multimedia/playlists', $foreign->getId()), [], [], $this->authHeaders($user));

        self::assertResponseStatusCodeSame(403);
    }

    private function multimediaScenario(string $email): array
    {
        $user = $this->createUser($email);
        $household = $this->createHousehold('Casa '.$email);
        $this->addMember($user, $household, 'owner');

        return [$user, $household];
    }
}
