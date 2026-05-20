<?php

namespace App\Tests\Functional;

use App\Tests\Support\ApiTestCase;

class AuthApiTest extends ApiTestCase
{
    public function testLoginReturnsJwtForActiveUser(): void
    {
        $this->createUser('login.ok@example.test');
        $this->flush();

        $this->json('POST', '/api/login_check', [
            'email' => 'login.ok@example.test',
            'password' => 'HomeTab2026!Test',
        ]);

        self::assertResponseIsSuccessful();
        $data = $this->responseJson();

        self::assertArrayHasKey('token', $data);
        self::assertSame('login.ok@example.test', $data['user']['email']);
        self::assertFalse($data['twoFactorEnabled']);
    }

    public function testLoginRejectsInvalidPassword(): void
    {
        $this->createUser('login.bad@example.test');
        $this->flush();

        $this->json('POST', '/api/login_check', [
            'email' => 'login.bad@example.test',
            'password' => 'wrong-password',
        ]);

        self::assertResponseStatusCodeSame(401);
        self::assertSame('Credenciales invalidas.', $this->responseJson()['error']);
    }

    public function testLoginRejectsInactiveUser(): void
    {
        $this->createUser('login.inactive@example.test', active: false);
        $this->flush();

        $this->json('POST', '/api/login_check', [
            'email' => 'login.inactive@example.test',
            'password' => 'HomeTab2026!Test',
        ]);

        self::assertResponseStatusCodeSame(401);
    }
}
