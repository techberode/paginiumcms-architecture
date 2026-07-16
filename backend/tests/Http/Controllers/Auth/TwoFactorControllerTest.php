<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Auth;

use PaginiumCMS\Tests\Http\TestCase;
use PaginiumCMS\Modules\Security\Services\TOTPGenerator;

class TwoFactorControllerTest extends TestCase
{
    public function testEnableTwoFactor(): void
    {
        $userData = $this->createTestUser();
        $this->assertEquals(201, $userData['response']->getStatusCode());

        $loginResult = $this->loginTestUser($userData['email'], $userData['password']);
        $this->assertEquals(200, $loginResult['response']->getStatusCode());

        $request = $this->createJsonRequest('POST', '/api/auth/2fa/enable');
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('secret', $data);
        $this->assertArrayHasKey('qr_code', $data);
        $this->assertArrayHasKey('provisioning_uri', $data);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $data['qr_code']);
    }

    public function testGetTwoFactorStatus(): void
    {
        $userData = $this->createTestUser();
        $this->assertEquals(201, $userData['response']->getStatusCode());

        $loginResult = $this->loginTestUser($userData['email'], $userData['password']);
        $this->assertEquals(200, $loginResult['response']->getStatusCode());

        $request = $this->createJsonRequest('GET', '/api/auth/2fa/status');
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($data['enabled']);
        $this->assertFalse($data['verified']);
    }

    public function testEnableAndVerifyTwoFactor(): void
    {
        $userData = $this->createTestUser();
        $this->assertEquals(201, $userData['response']->getStatusCode());

        $loginResult = $this->loginTestUser($userData['email'], $userData['password']);
        $this->assertEquals(200, $loginResult['response']->getStatusCode());

        // Aktivácia 2FA
        $enableRequest = $this->createJsonRequest('POST', '/api/auth/2fa/enable');
        $enableResponse = $this->handleRequest($enableRequest);
        $enableData = $this->getJsonResponse($enableResponse);

        $this->assertEquals(200, $enableResponse->getStatusCode(), 'Aktivácia 2FA zlyhala: ' . json_encode($enableData));
        $this->assertTrue($enableData['success'], 'Aktivácia 2FA nevrátila success: ' . json_encode($enableData));
        $secret = $enableData['secret'];

        // Získanie TOTP kódu
        $totpGenerator = new TOTPGenerator();
        $code = $totpGenerator->getCurrentCode($secret);

        // Overenie TOTP kódu
        $verifyRequest = $this->createJsonRequest('POST', '/api/auth/2fa/verify', [
            'code' => $code,
        ]);

        $verifyResponse = $this->handleRequest($verifyRequest);
        $verifyData = $this->getJsonResponse($verifyResponse);

        $this->assertEquals(200, $verifyResponse->getStatusCode(), 'Overenie TOTP zlyhalo: ' . json_encode($verifyData));
        $this->assertTrue($verifyData['success'], 'Overenie TOTP nevrátilo success: ' . json_encode($verifyData));
        $this->assertEquals('TOTP kód bol úspešne overený', $verifyData['message']);

        // Overenie stavu 2FA
        $statusRequest = $this->createJsonRequest('GET', '/api/auth/2fa/status');
        $statusResponse = $this->handleRequest($statusRequest);
        $statusData = $this->getJsonResponse($statusResponse);

        $this->assertTrue($statusData['enabled'], '2FA nie je aktivovaná: ' . json_encode($statusData));
        $this->assertTrue($statusData['verified'], '2FA nie je overená: ' . json_encode($statusData));
    }

    public function testDisableTwoFactor(): void
    {
        $userData = $this->createTestUser();
        $this->assertEquals(201, $userData['response']->getStatusCode());

        $loginResult = $this->loginTestUser($userData['email'], $userData['password']);
        $this->assertEquals(200, $loginResult['response']->getStatusCode());

        // Aktivácia 2FA
        $enableRequest = $this->createJsonRequest('POST', '/api/auth/2fa/enable');
        $enableResponse = $this->handleRequest($enableRequest);
        $enableData = $this->getJsonResponse($enableResponse);
        $this->assertEquals(200, $enableResponse->getStatusCode(), 'Aktivácia 2FA zlyhala');
        $this->assertTrue($enableData['success'], 'Aktivácia 2FA nevrátila success');

        // Najprv overíme TOTP (inak sa nedá deaktivovať)
        $secret = $enableData['secret'];
        $totpGenerator = new TOTPGenerator();
        $code = $totpGenerator->getCurrentCode($secret);

        $verifyRequest = $this->createJsonRequest('POST', '/api/auth/2fa/verify', [
            'code' => $code,
        ]);
        $verifyResponse = $this->handleRequest($verifyRequest);
        $this->assertEquals(200, $verifyResponse->getStatusCode(), 'Overenie TOTP pred deaktiváciou zlyhalo');

        // Deaktivácia 2FA
        $disableRequest = $this->createJsonRequest('POST', '/api/auth/2fa/disable');
        $disableResponse = $this->handleRequest($disableRequest);
        $disableData = $this->getJsonResponse($disableResponse);

        $this->assertEquals(200, $disableResponse->getStatusCode(), 'Deaktivácia 2FA zlyhala: ' . json_encode($disableData));
        $this->assertTrue($disableData['success'], 'Deaktivácia 2FA nevrátila success');
        $this->assertEquals('2FA bola úspešne deaktivovaná', $disableData['message']);

        // Overenie stavu 2FA
        $statusRequest = $this->createJsonRequest('GET', '/api/auth/2fa/status');
        $statusResponse = $this->handleRequest($statusRequest);
        $statusData = $this->getJsonResponse($statusResponse);

        $this->assertFalse($statusData['enabled'], '2FA by mala byť deaktivovaná: ' . json_encode($statusData));
        $this->assertFalse($statusData['verified'], '2FA by nemala byť overená: ' . json_encode($statusData));
    }

    public function testGetQRCode(): void
    {
        $userData = $this->createTestUser();
        $this->assertEquals(201, $userData['response']->getStatusCode());

        $loginResult = $this->loginTestUser($userData['email'], $userData['password']);
        $this->assertEquals(200, $loginResult['response']->getStatusCode());

        // Aktivácia 2FA
        $enableRequest = $this->createJsonRequest('POST', '/api/auth/2fa/enable');
        $enableResponse = $this->handleRequest($enableRequest);
        $enableData = $this->getJsonResponse($enableResponse);
        $this->assertEquals(200, $enableResponse->getStatusCode(), 'Aktivácia 2FA zlyhala');
        $this->assertTrue($enableData['success'], 'Aktivácia 2FA nevrátila success');

        // Najprv overíme TOTP
        $secret = $enableData['secret'];
        $totpGenerator = new TOTPGenerator();
        $code = $totpGenerator->getCurrentCode($secret);

        $verifyRequest = $this->createJsonRequest('POST', '/api/auth/2fa/verify', [
            'code' => $code,
        ]);
        $verifyResponse = $this->handleRequest($verifyRequest);
        $this->assertEquals(200, $verifyResponse->getStatusCode(), 'Overenie TOTP pred získaním QR kódu zlyhalo');

        // Získanie QR kódu
        $qrRequest = $this->createJsonRequest('GET', '/api/auth/2fa/qr-code');
        $qrResponse = $this->handleRequest($qrRequest);
        $qrData = $this->getJsonResponse($qrResponse);

        $this->assertEquals(200, $qrResponse->getStatusCode(), 'Získanie QR kódu zlyhalo: ' . json_encode($qrData));
        $this->assertTrue($qrData['success'], 'Získanie QR kódu nevrátilo success');
        $this->assertArrayHasKey('qr_code', $qrData);
        $this->assertArrayHasKey('provisioning_uri', $qrData);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $qrData['qr_code']);
    }

    public function testVerifyLoginWithTwoFactor(): void
    {
        $userData = $this->createTestUser();
        $this->assertEquals(201, $userData['response']->getStatusCode());

        $loginResult = $this->loginTestUser($userData['email'], $userData['password']);
        $this->assertEquals(200, $loginResult['response']->getStatusCode());

        // Aktivácia 2FA
        $enableRequest = $this->createJsonRequest('POST', '/api/auth/2fa/enable');
        $enableResponse = $this->handleRequest($enableRequest);
        $enableData = $this->getJsonResponse($enableResponse);
        $this->assertEquals(200, $enableResponse->getStatusCode());
        $this->assertTrue($enableData['success']);
        $secret = $enableData['secret'];

        // Overenie TOTP
        $totpGenerator = new TOTPGenerator();
        $code = $totpGenerator->getCurrentCode($secret);

        $verifyRequest = $this->createJsonRequest('POST', '/api/auth/2fa/verify', [
            'code' => $code,
        ]);
        $verifyResponse = $this->handleRequest($verifyRequest);
        $this->assertEquals(200, $verifyResponse->getStatusCode());

        // Odhlásenie
        $logoutRequest = $this->createJsonRequest('POST', '/api/auth/logout');
        $logoutResponse = $this->handleRequest($logoutRequest);
        $this->assertEquals(200, $logoutResponse->getStatusCode());

        // Prihlásenie – malo by vrátiť requires_two_factor = true
        $loginResult = $this->loginTestUser($userData['email'], $userData['password']);
        $this->assertEquals(200, $loginResult['response']->getStatusCode());
        $this->assertTrue($loginResult['data']['requires_two_factor']);

        // Overenie TOTP kódu pri prihlásení
        $code = $totpGenerator->getCurrentCode($secret);

        $verifyRequest = $this->createJsonRequest('POST', '/api/auth/2fa/verify-login', [
            'code' => $code,
        ]);
        $verifyResponse = $this->handleRequest($verifyRequest);
        $verifyData = $this->getJsonResponse($verifyResponse);

        $this->assertEquals(200, $verifyResponse->getStatusCode());
        $this->assertTrue($verifyData['success']);
        $this->assertEquals('TOTP kód bol úspešne overený', $verifyData['message']);
        $this->assertEquals($userData['email'], $verifyData['user']['email']);
    }
}
