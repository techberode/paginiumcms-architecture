<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Auth;

use PaginiumCMS\Tests\Http\TestCase;
use PaginiumCMS\Modules\Security\Services\TOTPGenerator;

class TwoFactorSimpleTest extends TestCase
{
    public function testSimpleTwoFactorFlow(): void
    {
        // 1. Registrácia
        $userData = $this->createTestUser();
        $this->assertEquals(201, $userData['response']->getStatusCode());

        // 2. Prihlásenie
        $loginResult = $this->loginTestUser($userData['email'], $userData['password']);
        $this->assertEquals(200, $loginResult['response']->getStatusCode());

        // 3. Aktivácia 2FA
        $enableRequest = $this->createJsonRequest('POST', '/api/auth/2fa/enable');
        $enableResponse = $this->handleRequest($enableRequest);
        $enableData = $this->getJsonResponse($enableResponse);

        $this->assertEquals(200, $enableResponse->getStatusCode());
        $this->assertTrue($enableData['success']);
        $secret = $enableData['secret'];

        // 4. Overenie TOTP
        $totpGenerator = new TOTPGenerator();
        $code = $totpGenerator->getCurrentCode($secret);

        $verifyRequest = $this->createJsonRequest('POST', '/api/auth/2fa/verify', [
            'code' => $code,
        ]);
        $verifyResponse = $this->handleRequest($verifyRequest);
        $verifyData = $this->getJsonResponse($verifyResponse);

        $this->assertEquals(200, $verifyResponse->getStatusCode());
        $this->assertTrue($verifyData['success']);
    }
}
