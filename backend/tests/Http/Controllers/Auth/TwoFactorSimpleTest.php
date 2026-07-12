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
        echo "\n✅ 1. Registrácia OK: " . $userData['email'] . "\n";

        // 2. Prihlásenie
        $loginResult = $this->loginTestUser($userData['email'], $userData['password']);
        $this->assertEquals(200, $loginResult['response']->getStatusCode());
        echo "✅ 2. Prihlásenie OK\n";

        // 3. Aktivácia 2FA
        $enableRequest = $this->createJsonRequest('POST', '/api/auth/2fa/enable');
        $enableResponse = $this->handleRequest($enableRequest);
        $enableData = $this->getJsonResponse($enableResponse);
        
        echo "🔍 3. Aktivácia 2FA: status=" . $enableResponse->getStatusCode() . ", data=" . json_encode($enableData) . "\n";
        
        $this->assertEquals(200, $enableResponse->getStatusCode());
        $this->assertTrue($enableData['success']);
        $secret = $enableData['secret'];
        echo "✅ 3. Aktivácia 2FA OK, secret=" . $secret . "\n";

        // 4. Overenie TOTP
        $totpGenerator = new TOTPGenerator();
        $code = $totpGenerator->getCurrentCode($secret);
        echo "🔍 4. TOTP kód: " . $code . "\n";

        $verifyRequest = $this->createJsonRequest('POST', '/api/auth/2fa/verify', [
            'code' => $code,
        ]);
        $verifyResponse = $this->handleRequest($verifyRequest);
        $verifyData = $this->getJsonResponse($verifyResponse);
        
        echo "🔍 5. Overenie TOTP: status=" . $verifyResponse->getStatusCode() . ", data=" . json_encode($verifyData) . "\n";
        
        $this->assertEquals(200, $verifyResponse->getStatusCode());
        $this->assertTrue($verifyData['success']);
        echo "✅ 5. Overenie TOTP OK\n";
    }
}
