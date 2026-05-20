<?php

namespace App\Tests\Functional;

use App\Entity\TwoFactorCode;
use App\Repository\TwoFactorCodeRepository;
use App\Tests\Support\ApiTestCase;

class AuthTwoFactorApiTest extends ApiTestCase
{
    public function testLoginWithTwoFactorReturnsChallenge(): void
    {
        $user = $this->createUser('2fa.challenge@example.test');
        $user->setTwoFactorEnabled(true);
        $this->flush();

        $this->json('POST', '/api/login_check', [
            'username' => '2fa.challenge@example.test',
            'password' => 'HomeTab2026!Test',
        ]);

        self::assertResponseIsSuccessful();
        $payload = $this->responseJson();

        self::assertTrue($payload['requiresTwoFactor']);
        self::assertNotEmpty($payload['challengeId']);
        self::assertArrayNotHasKey('token', $payload);
    }

    public function testTwoFactorLoginRejectsInvalidCode(): void
    {
        $challenge = $this->loginChallenge('2fa.invalid@example.test');

        $this->json('POST', '/api/login/verify', [
            'challengeId' => $challenge,
            'code' => '000000',
        ]);

        self::assertResponseStatusCodeSame(400);
    }

    public function testTwoFactorLoginReturnsJwtWithValidCode(): void
    {
        $challenge = $this->loginChallenge('2fa.valid@example.test');
        $this->setChallengeCode($challenge, '123456');

        $this->json('POST', '/api/login/verify', [
            'challengeId' => $challenge,
            'code' => '123456',
        ]);

        self::assertResponseIsSuccessful();
        $payload = $this->responseJson();

        self::assertNotEmpty($payload['token']);
        self::assertTrue($payload['twoFactorEnabled']);
    }

    public function testEnableAndDisableTwoFactorRequireAuthenticatedUser(): void
    {
        $this->json('POST', '/api/2fa/enable/start');
        self::assertResponseStatusCodeSame(401);

        $this->json('POST', '/api/2fa/disable');
        self::assertResponseStatusCodeSame(401);
    }

    public function testAuthenticatedUserCanEnableAndDisableTwoFactor(): void
    {
        $user = $this->createUser('2fa.enable@example.test');
        $this->flush();

        $this->json('POST', '/api/2fa/enable/start', [], $this->authHeaders($user));
        self::assertResponseIsSuccessful();
        $challenge = $this->responseJson()['challengeId'];
        $this->setChallengeCode($challenge, '654321');

        $this->json('POST', '/api/2fa/enable/verify', [
            'challengeId' => $challenge,
            'code' => '654321',
        ], $this->authHeaders($user));
        self::assertResponseIsSuccessful();
        self::assertTrue($this->responseJson()['twoFactorEnabled']);

        $this->json('POST', '/api/2fa/disable', [], $this->authHeaders($user));
        self::assertResponseIsSuccessful();
        self::assertFalse($this->responseJson()['twoFactorEnabled']);
    }

    private function loginChallenge(string $email): string
    {
        $user = $this->createUser($email);
        $user->setTwoFactorEnabled(true);
        $this->flush();

        $this->json('POST', '/api/login_check', [
            'username' => $email,
            'password' => 'HomeTab2026!Test',
        ]);

        self::assertResponseIsSuccessful();

        return $this->responseJson()['challengeId'];
    }

    private function setChallengeCode(string $challengeId, string $plainCode): void
    {
        $repository = static::getContainer()->get(TwoFactorCodeRepository::class);
        $challenge = $repository->findOneBy(['challengeId' => $challengeId]);

        self::assertInstanceOf(TwoFactorCode::class, $challenge);
        $challenge->setCodeHash(hash('sha256', $plainCode));
        $this->flush();
    }
}
