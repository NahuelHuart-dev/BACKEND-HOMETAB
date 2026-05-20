<?php

namespace App\Tests\Functional;

use App\Tests\Support\ApiTestCase;

class ProfileApiTest extends ApiTestCase
{
    public function testAuthenticatedUserCanReadProfile(): void
    {
        $user = $this->createUser('profile.read@example.test');
        $user->setFirstName('Ada')->setLastName('Lovelace');
        $this->flush();

        $this->client->request('GET', '/api/profile', [], [], $this->authHeaders($user));

        self::assertResponseIsSuccessful();
        $payload = $this->responseJson();
        self::assertSame('profile.read@example.test', $payload['email']);
        self::assertSame('Ada Lovelace', $payload['fullName']);
    }

    public function testAuthenticatedUserCanUpdateProfileFields(): void
    {
        $user = $this->createUser('profile.update@example.test');
        $this->flush();

        $this->json('POST', '/api/profile', [
            'firstName' => 'Naomi',
            'lastName' => 'Home',
            'phoneNumber' => '600123456',
            'bio' => 'Organizando HomeTab',
            'avatarIcon' => 'pi-star',
        ], $this->authHeaders($user));

        self::assertResponseIsSuccessful();
        $profile = $this->responseJson()['user'];
        self::assertSame('Naomi Home', $profile['fullName']);
        self::assertSame('600123456', $profile['phoneNumber']);
        self::assertSame('Organizando HomeTab', $profile['bio']);
        self::assertSame('pi-star', $profile['avatarIcon']);
    }

    public function testProfileRejectsDuplicatedEmail(): void
    {
        $user = $this->createUser('profile.duplicate@example.test');
        $this->createUser('profile.existing@example.test');
        $this->flush();

        $this->json('POST', '/api/profile', [
            'email' => 'profile.existing@example.test',
        ], $this->authHeaders($user));

        self::assertResponseStatusCodeSame(400);
    }

    public function testProfileRejectsWrongCurrentPassword(): void
    {
        $user = $this->createUser('profile.password@example.test');
        $this->flush();

        $this->json('POST', '/api/profile', [
            'currentPassword' => 'wrong-password',
            'newPassword' => 'HomeTab2026!New',
            'confirmPassword' => 'HomeTab2026!New',
        ], $this->authHeaders($user));

        self::assertResponseStatusCodeSame(400);
    }

    public function testProfileRejectsInvalidAvatarData(): void
    {
        $user = $this->createUser('profile.avatar@example.test');
        $this->flush();

        $this->json('POST', '/api/profile', [
            'avatarCropData' => 'data:text/plain;base64,SG9tZVRhYg==',
        ], $this->authHeaders($user));

        self::assertResponseStatusCodeSame(400);
    }
}
