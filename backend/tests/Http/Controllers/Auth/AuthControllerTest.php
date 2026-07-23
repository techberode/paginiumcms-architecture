<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Auth;

use PaginiumCMS\Tests\Http\TestCase;

class AuthControllerTest extends TestCase
{
    public function testRegisterSuccess(): void
    {
        $email = 'test_' . uniqid() . '@example.com';
        $password = 'StrongP@ssw0rd123!';
        $name = 'Test User';

        $request = $this->createJsonRequest('POST', '/api/auth/register', [
            'email' => $email,
            'password' => $password,
            'passwordConfirm' => $password,
            'name' => $name,
        ]);

        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertEquals('Registrácia prebehla úspešne', $data['message']);
        $this->assertEquals($email, $data['user']['email']);
        $this->assertEquals($name, $data['user']['name']);
        $this->assertEquals(['USER'], $data['user']['roles']);
        $this->assertFalse($data['user']['twoFactorEnabled']);
    }

    public function testRegisterWithExistingEmail(): void
    {
        // Vytvoríme používateľa
        $userData = $this->createTestUser();
        $this->assertEquals(201, $userData['response']->getStatusCode());

        // Skúsime zaregistrovať rovnaký email
        $request = $this->createJsonRequest('POST', '/api/auth/register', [
            'email' => $userData['email'],
            'password' => 'AnotherP@ssw0rd123!',
            'passwordConfirm' => 'AnotherP@ssw0rd123!',
            'name' => 'Another User',
        ]);

        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(409, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('už existuje', $data['error']);
    }

    public function testRegisterWithWeakPassword(): void
    {
        $request = $this->createJsonRequest('POST', '/api/auth/register', [
            'email' => 'test@example.com',
            'password' => 'weak',
            'passwordConfirm' => 'weak',
            'name' => 'Test User',
        ]);

        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Heslo nespĺňa požiadavky', $data['error']);
    }

    public function testRegisterRejectsMismatchedPasswordConfirmation(): void
    {
        $request = $this->createJsonRequest('POST', '/api/auth/register', [
            'email' => 'mismatch_' . uniqid() . '@example.com',
            'password' => 'StrongP@ssw0rd123!',
            'passwordConfirm' => 'StrongP@ssw0rd123?',
            'name' => 'Mismatch User',
        ]);

        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('nezhodujú', (string) ($data['error'] ?? ''));
    }

    public function testRegisterWithOtpEnabled(): void
    {
        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';

        $settings = $this->app->getContainer()->get(\PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface::class);
        $settings->setGroup('workflows', [
            'registrationOtpEnabled' => true,
            'otpTtlMinutes' => 15,
            'otpMaxAttempts' => 5,
        ]);

        $email = 'otp_reg_' . uniqid() . '@example.com';
        $password = 'StrongP@ssw0rd123!';
        $name = 'OTP Register User';

        $registerRequest = $this->createJsonRequest('POST', '/api/auth/register', [
            'email' => $email,
            'password' => $password,
            'passwordConfirm' => $password,
            'name' => $name,
        ]);
        $registerResponse = $this->handleRequest($registerRequest);
        $registerData = $this->getJsonResponse($registerResponse);

        $this->assertEquals(202, $registerResponse->getStatusCode());
        $this->assertTrue($registerData['requires_otp']);
        $this->assertNotEmpty($registerData['challenge_id']);
        $this->assertArrayHasKey('debug_code', $registerData);

        $verifyRequest = $this->createJsonRequest('POST', '/api/auth/register/verify-otp', [
            'challenge_id' => $registerData['challenge_id'],
            'code' => $registerData['debug_code'],
        ]);
        $verifyResponse = $this->handleRequest($verifyRequest);
        $verifyData = $this->getJsonResponse($verifyResponse);

        $this->assertEquals(201, $verifyResponse->getStatusCode());
        $this->assertTrue($verifyData['success']);
        $this->assertEquals($email, $verifyData['user']['email']);
        $this->assertEquals($name, $verifyData['user']['name']);

        $loginRequest = $this->createJsonRequest('POST', '/api/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);
        $loginResponse = $this->handleRequest($loginRequest);
        $this->assertEquals(200, $loginResponse->getStatusCode());
    }

    public function testLoginSuccess(): void
    {
        $userData = $this->createTestUser();
        $this->assertEquals(201, $userData['response']->getStatusCode());

        $request = $this->createJsonRequest('POST', '/api/auth/login', [
            'email' => $userData['email'],
            'password' => $userData['password'],
        ]);

        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertEquals($userData['email'], $data['user']['email']);
    }

    public function testLoginWithInvalidPassword(): void
    {
        $userData = $this->createTestUser();
        $this->assertEquals(201, $userData['response']->getStatusCode());

        $request = $this->createJsonRequest('POST', '/api/auth/login', [
            'email' => $userData['email'],
            'password' => 'WrongPassword123!',
        ]);

        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Neplatný email alebo heslo', $data['error']);
    }

    public function testLoginWithNonExistentEmail(): void
    {
        $request = $this->createJsonRequest('POST', '/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'SomePassword123!',
        ]);

        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Neplatný email alebo heslo', $data['error']);
    }

    public function testLoginLockoutAfterRepeatedFailures(): void
    {
        $settings = $this->app->getContainer()->get(\PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface::class);
        $settings->setGroup('security', [
            'maxLoginAttempts' => 3,
            'lockoutMinutes' => 15,
        ]);

        $email = 'lockout_' . uniqid() . '@example.com';
        $ip = '10.99.' . random_int(1, 254) . '.' . random_int(1, 254);
        $factory = new \Slim\Psr7\Factory\ServerRequestFactory();

        for ($i = 0; $i < 3; $i++) {
            $request = $factory->createServerRequest('POST', '/api/auth/login', ['REMOTE_ADDR' => $ip]);
            $body = (new \Slim\Psr7\Factory\StreamFactory())->createStream(
                \PaginiumCMS\Support\JsonHelper::encode([
                    'email' => $email,
                    'password' => 'WrongPassword123!',
                ])
            );
            $request = $request->withBody($body)->withHeader('Content-Type', 'application/json');

            $response = $this->handleRequest($request);
            $this->assertEquals(401, $response->getStatusCode(), 'Attempt ' . ($i + 1));
        }

        $lockedRequest = $factory->createServerRequest('POST', '/api/auth/login', ['REMOTE_ADDR' => $ip]);
        $lockedBody = (new \Slim\Psr7\Factory\StreamFactory())->createStream(
            \PaginiumCMS\Support\JsonHelper::encode([
                'email' => $email,
                'password' => 'WrongPassword123!',
            ])
        );
        $lockedRequest = $lockedRequest->withBody($lockedBody)->withHeader('Content-Type', 'application/json');
        $lockedResponse = $this->handleRequest($lockedRequest);
        $data = $this->getJsonResponse($lockedResponse);

        $this->assertEquals(429, $lockedResponse->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Príliš veľa neúspešných pokusov', $data['error']);
    }

    public function testLogout(): void
    {
        $userData = $this->createTestUser();
        $this->assertEquals(201, $userData['response']->getStatusCode());

        $loginRequest = $this->createJsonRequest('POST', '/api/auth/login', [
            'email' => $userData['email'],
            'password' => $userData['password'],
        ]);
        $loginResponse = $this->handleRequest($loginRequest);
        $this->assertEquals(200, $loginResponse->getStatusCode());

        $logoutRequest = $this->createJsonRequest('POST', '/api/auth/logout');
        $logoutResponse = $this->handleRequest($logoutRequest);
        $data = $this->getJsonResponse($logoutResponse);

        $this->assertEquals(200, $logoutResponse->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertEquals('Odhlásenie prebehlo úspešne', $data['message']);
    }

    public function testGetCurrentUser(): void
    {
        $userData = $this->createTestUser();
        $this->assertEquals(201, $userData['response']->getStatusCode());

        $loginRequest = $this->createJsonRequest('POST', '/api/auth/login', [
            'email' => $userData['email'],
            'password' => $userData['password'],
        ]);
        $loginResponse = $this->handleRequest($loginRequest);
        $this->assertEquals(200, $loginResponse->getStatusCode());

        $request = $this->createJsonRequest('GET', '/api/auth/me');
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($userData['email'], $data['user']['email']);
        $this->assertEquals($userData['name'], $data['user']['name']);
    }

    public function testChangePasswordSuccess(): void
    {
        $userData = $this->createTestUser();
        $this->assertEquals(201, $userData['response']->getStatusCode());

        $loginRequest = $this->createJsonRequest('POST', '/api/auth/login', [
            'email' => $userData['email'],
            'password' => $userData['password'],
        ]);
        $loginResponse = $this->handleRequest($loginRequest);
        $this->assertEquals(200, $loginResponse->getStatusCode());

        $newPassword = 'NewStrongP@ssw0rd123!';
        $request = $this->createJsonRequest('POST', '/api/auth/change-password', [
            'old_password' => $userData['password'],
            'new_password' => $newPassword,
        ]);

        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertEquals('Heslo bolo úspešne zmenené', $data['message']);

        // Overíme prihlásenie s novým heslom
        $loginRequest = $this->createJsonRequest('POST', '/api/auth/login', [
            'email' => $userData['email'],
            'password' => $newPassword,
        ]);
        $loginResponse = $this->handleRequest($loginRequest);
        $this->assertEquals(200, $loginResponse->getStatusCode());
    }

    public function testChangePasswordWithWrongOldPassword(): void
    {
        $userData = $this->createTestUser();
        $this->assertEquals(201, $userData['response']->getStatusCode());

        $loginRequest = $this->createJsonRequest('POST', '/api/auth/login', [
            'email' => $userData['email'],
            'password' => $userData['password'],
        ]);
        $loginResponse = $this->handleRequest($loginRequest);
        $this->assertEquals(200, $loginResponse->getStatusCode());

        $request = $this->createJsonRequest('POST', '/api/auth/change-password', [
            'old_password' => 'WrongPassword123!',
            'new_password' => 'NewStrongP@ssw0rd123!',
        ]);

        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Staré heslo nie je správne', $data['error']);
    }

    public function testResetPassword(): void
    {
        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';

        $userData = $this->createTestUser();
        $this->assertEquals(201, $userData['response']->getStatusCode());

        $request = $this->createJsonRequest('POST', '/api/auth/reset-password', [
            'email' => $userData['email'],
        ]);

        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('token', $data, 'Token should be returned in testing when SMTP is not configured');

        $newPassword = 'ResetPassword123!';
        $verifyRequest = $this->createJsonRequest('POST', '/api/auth/verify-reset-token', [
            'token' => $data['token'],
            'new_password' => $newPassword,
        ]);

        $verifyResponse = $this->handleRequest($verifyRequest);
        $verifyData = $this->getJsonResponse($verifyResponse);

        $this->assertEquals(200, $verifyResponse->getStatusCode());
        $this->assertTrue($verifyData['success']);
        $this->assertEquals('Heslo bolo úspešne zmenené', $verifyData['message']);

        $loginRequest = $this->createJsonRequest('POST', '/api/auth/login', [
            'email' => $userData['email'],
            'password' => $newPassword,
        ]);
        $loginResponse = $this->handleRequest($loginRequest);
        $this->assertEquals(200, $loginResponse->getStatusCode());
    }

    public function testRoutesAreRegistered(): void
    {
        // Tento test len overí, či routy existujú
        $request = $this->createJsonRequest('GET', '/api/auth/csrf-token?key=test');
        $response = $this->handleRequest($request);

        // Ak routa neexistuje, vráti 404
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    public function testGetCsrfToken(): void
    {
        $request = $this->createJsonRequest('GET', '/api/auth/csrf-token?key=test_form');
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('key', $data);
        $this->assertEquals('test_form', $data['key']);
        $this->assertNotEmpty($data['token']);
        $this->assertEquals(64, strlen($data['token']));
    }
}
