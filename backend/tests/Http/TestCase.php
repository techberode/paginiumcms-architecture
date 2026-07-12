<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Psr\Http\Message\ServerRequestInterface;
use PaginiumCMS\Modules\Security\Models\User;

abstract class TestCase extends BaseTestCase
{
    protected App $app;
    protected ?User $currentUser = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        session_start();
        $_SESSION = [];

        $this->app = require __DIR__ . '/../../bootstrap/app.php';
        $this->currentUser = null;
    }

    protected function tearDown(): void
    {
        session_destroy();
        parent::tearDown();
    }

    protected function createJsonRequest(
        string $method,
        string $uri,
        array $data = null,
        array $headers = []
    ): ServerRequestInterface {
        $request = (new ServerRequestFactory())->createServerRequest($method, $uri);

        if ($data !== null) {
            $body = (new StreamFactory())->createStream(json_encode($data));
            $request = $request->withBody($body);
            $request = $request->withHeader('Content-Type', 'application/json');
        }

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        // Ak máme aktuálneho používateľa, pridáme ho do atribútov
        if ($this->currentUser !== null) {
            $request = $request->withAttribute('user', $this->currentUser);
        }

        return $request;
    }

    protected function handleRequest(ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        return $this->app->handle($request);
    }

    protected function getJsonResponse(\Psr\Http\Message\ResponseInterface $response): array
    {
        return json_decode((string)$response->getBody(), true) ?? [];
    }

    protected function createTestUser(string $email = null, string $password = null, string $name = null): array
    {
        $email = $email ?? 'test_' . uniqid() . '@example.com';
        $password = $password ?? 'StrongP@ssw0rd123!';
        $name = $name ?? 'Test User';

        $request = $this->createJsonRequest('POST', '/api/auth/register', [
            'email' => $email,
            'password' => $password,
            'name' => $name,
        ]);

        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        return [
            'email' => $email,
            'password' => $password,
            'name' => $name,
            'user' => $data['user'] ?? null,
            'response' => $response,
        ];
    }

    protected function loginTestUser(string $email, string $password): array
    {
        $request = $this->createJsonRequest('POST', '/api/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        if (isset($data['user'])) {
            // Vytvoríme User objekt z dát
            $user = new User();
            // Naplníme User objekt (zjednodušené – v reálnom svete by sme použili UserRepository)
            $reflection = new \ReflectionClass($user);
            foreach ($data['user'] as $key => $value) {
                if ($key === 'id') {
                    $prop = $reflection->getProperty('id');
                    $prop->setAccessible(true);
                    $prop->setValue($user, $value);
                } elseif ($key === 'email') {
                    $user->setEmail($value);
                } elseif ($key === 'name') {
                    $user->setName($value);
                } elseif ($key === 'roles') {
                    $user->setRoles($value);
                } elseif ($key === 'twoFactorEnabled') {
                    $user->setTwoFactorEnabled($value);
                }
            }
            $this->currentUser = $user;
        }

        return [
            'response' => $response,
            'data' => $data,
        ];
    }

    /**
     * Nastaví aktuálneho používateľa priamo (pre testy, ktoré potrebujú prihláseného používateľa).
     */
    protected function setCurrentUser(User $user): void
    {
        $this->currentUser = $user;
    }
}
