<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http;

use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PaginiumCMS\Support\JsonHelper;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;

abstract class TestCase extends BaseTestCase
{
    /** @var App<ContainerInterface> */
    protected App $app;
    protected ?User $currentUser = null;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';

        $this->clearRateLimitCache();
        $this->clearLoginAttemptStore();
        $this->clearFirewallStore();
        $this->clearOtpChallengeStore();

        $this->clearTrashStore();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        session_start();
        $_SESSION = [];

        $this->app = require __DIR__ . '/../../bootstrap/app.php';
        $this->currentUser = null;

        $this->resetPersistedTestSettings();
        $this->clearApplicationCache();
    }

    private function resetPersistedTestSettings(): void
    {
        $settings = $this->app->getContainer()->get(SettingsRepositoryInterface::class);

        $settings->setGroup('general', array_merge($settings->group('general'), [
            'maintenanceMode' => false,
        ]));

        $settings->setGroup('workflows', array_merge($settings->group('workflows'), [
            'registrationOtpEnabled' => false,
            'commentApprovalOtpEnabled' => false,
            'publishApprovalOtpEnabled' => false,
        ]));
    }

    private function clearApplicationCache(): void
    {
        $this->app->getContainer()->get(CacheManager::class)->clear();
    }

    private function clearOtpChallengeStore(): void
    {
        $path = __DIR__ . '/../../storage/app/content/data/otp-challenges.json';
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function clearTrashStore(): void
    {
        $dir = __DIR__ . '/../../storage/app/content/trash';
        if (!is_dir($dir)) {
            return;
        }

        foreach (glob($dir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    private function clearLoginAttemptStore(): void
    {
        $path = __DIR__ . '/../../storage/app/content/data/security/login_attempts.json';
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function clearFirewallStore(): void
    {
        $dir = __DIR__ . '/../../storage/app/content/data/security/firewall';
        if (!is_dir($dir)) {
            return;
        }

        foreach (glob($dir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    private function clearRateLimitCache(): void
    {
        $cachePath = __DIR__ . '/../../storage/cache';
        foreach (glob($cachePath . '/*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        parent::tearDown();
    }

    /**
     * @param array<int|string, mixed>|null $data
     * @param array<string, string> $headers
     */
    protected function createJsonRequest(
        string $method,
        string $uri,
        ?array $data = null,
        array $headers = []
    ): ServerRequestInterface {
        $request = (new ServerRequestFactory())->createServerRequest($method, $uri);

        if ($data !== null) {
            $body = (new StreamFactory())->createStream(JsonHelper::encode($data));
            $request = $request->withBody($body);
            $request = $request->withHeader('Content-Type', 'application/json');
        }

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($this->currentUser !== null) {
            $request = $request->withAttribute('user', $this->currentUser);
        }

        return $request;
    }

    protected function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        return $this->app->handle($request);
    }

    /**
     * @return array<int|string, mixed>
     */
    protected function getJsonResponse(ResponseInterface $response): array
    {
        $decoded = json_decode((string) $response->getBody(), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array{
     *     email: string,
     *     password: string,
     *     name: string,
     *     user: mixed,
     *     response: ResponseInterface
     * }
     */
    protected function createTestUser(
        ?string $email = null,
        ?string $password = null,
        ?string $name = null
    ): array {
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

    /**
     * @return array{
     *     email: string,
     *     password: string,
     *     name: string,
     *     user: mixed,
     *     response: ResponseInterface,
     *     data: array<int|string, mixed>
     * }
     */
    protected function loginAsAdminUser(
        ?string $email = null,
        ?string $password = null,
        ?string $name = null
    ): array {
        $userData = $this->createTestUser($email, $password, $name);

        $container = $this->app->getContainer();

        $repo = $container->get(UserRepository::class);
        $user = $repo->findByEmail($userData['email']);
        if ($user !== null) {
            $user->setRoles(['ADMIN']);
            $repo->save($user);
        }

        $login = $this->loginTestUser($userData['email'], $userData['password']);
        if ($this->currentUser !== null) {
            $this->currentUser->setRoles(['ADMIN']);
        }

        return array_merge($userData, $login);
    }

    /**
     * @return array{response: ResponseInterface, data: array<int|string, mixed>}
     */
    protected function loginTestUser(string $email, string $password): array
    {
        $request = $this->createJsonRequest('POST', '/api/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        if (isset($data['user']) && is_array($data['user'])) {
            $user = new User();
            $reflection = new \ReflectionClass($user);
            foreach ($data['user'] as $key => $value) {
                if ($key === 'id') {
                    $prop = $reflection->getProperty('id');
                    $prop->setValue($user, $value);
                } elseif ($key === 'email') {
                    $user->setEmail((string) $value);
                } elseif ($key === 'name') {
                    $user->setName((string) $value);
                } elseif ($key === 'roles' && is_array($value)) {
                    $user->setRoles($value);
                } elseif ($key === 'twoFactorEnabled') {
                    $user->setTwoFactorEnabled((bool) $value);
                }
            }
            $this->currentUser = $user;
        }

        return [
            'response' => $response,
            'data' => $data,
        ];
    }

    protected function setCurrentUser(User $user): void
    {
        $this->currentUser = $user;
    }
}
