<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Middleware;

use PaginiumCMS\Http\Middleware\CsrfMiddleware;
use PaginiumCMS\Modules\Security\Contracts\CsrfProtectionInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * Overuje CSRF enforcement (audit S3 / ISS-012).
 *
 * CsrfMiddleware je v APP_ENV=testing no-op, preto tu dočasne prepneme
 * prostredie na "production", aby sa logika reálne vykonala.
 */
final class CsrfMiddlewareTest extends TestCase
{
    private const VALID = 'valid-csrf-token';

    /** @var array{env: string|false, server: mixed, srv: mixed} */
    private array $savedEnv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->savedEnv = [
            'env' => getenv('APP_ENV'),
            'server' => $_ENV['APP_ENV'] ?? null,
            'srv' => $_SERVER['APP_ENV'] ?? null,
        ];
        putenv('APP_ENV=production');
        $_ENV['APP_ENV'] = 'production';
        $_SERVER['APP_ENV'] = 'production';
    }

    protected function tearDown(): void
    {
        if ($this->savedEnv['env'] === false) {
            putenv('APP_ENV');
        } else {
            putenv('APP_ENV=' . $this->savedEnv['env']);
        }
        $_ENV['APP_ENV'] = $this->savedEnv['server'];
        $_SERVER['APP_ENV'] = $this->savedEnv['srv'];
        parent::tearDown();
    }

    private function makeMiddleware(): CsrfMiddleware
    {
        $csrf = new class implements CsrfProtectionInterface {
            public function generateToken(string $key): string
            {
                return CsrfMiddlewareTest::validToken();
            }

            public function verifyToken(string $key, string $token): bool
            {
                return hash_equals(CsrfMiddlewareTest::validToken(), $token);
            }

            public function requireValidToken(string $key, string $token): void
            {
            }

            public function getToken(string $key): string
            {
                return CsrfMiddlewareTest::validToken();
            }

            public function clearToken(string $key): void
            {
            }
        };

        return new CsrfMiddleware($csrf);
    }

    public static function validToken(): string
    {
        return self::VALID;
    }

    private function handler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return (new ResponseFactory())->createResponse(200);
            }
        };
    }

    private function request(string $method, string $path, ?string $token = null): ServerRequestInterface
    {
        $request = (new ServerRequestFactory())->createServerRequest($method, $path);
        if ($token !== null) {
            $request = $request->withHeader('X-CSRF-TOKEN', $token);
        }

        return $request;
    }

    public function testSafeMethodPassesWithoutToken(): void
    {
        $response = $this->makeMiddleware()->process(
            $this->request('GET', '/api/pages'),
            $this->handler()
        );
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testExemptLoginPassesWithoutToken(): void
    {
        $response = $this->makeMiddleware()->process(
            $this->request('POST', '/api/auth/login'),
            $this->handler()
        );
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testSimilarPrefixDoesNotExemptCsrf(): void
    {
        $response = $this->makeMiddleware()->process(
            $this->request('POST', '/api/newsletter-admin/bulk'),
            $this->handler()
        );
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testExemptPrefixWithSubpathPassesWithoutToken(): void
    {
        $response = $this->makeMiddleware()->process(
            $this->request('POST', '/api/newsletter/subscribe'),
            $this->handler()
        );
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProtectedPostWithoutTokenIsRejected(): void
    {
        $response = $this->makeMiddleware()->process(
            $this->request('POST', '/api/pages'),
            $this->handler()
        );
        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('csrf_invalid', (string) $response->getBody());
    }

    public function testProtectedPostWithInvalidTokenIsRejected(): void
    {
        $response = $this->makeMiddleware()->process(
            $this->request('DELETE', '/api/pages/hello', 'wrong-token'),
            $this->handler()
        );
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testProtectedPostWithValidTokenPasses(): void
    {
        $response = $this->makeMiddleware()->process(
            $this->request('POST', '/api/pages', self::VALID),
            $this->handler()
        );
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testTestingEnvironmentBypassesEnforcement(): void
    {
        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';
        $_SERVER['APP_ENV'] = 'testing';

        $response = $this->makeMiddleware()->process(
            $this->request('POST', '/api/pages'),
            $this->handler()
        );

        $this->assertSame(200, $response->getStatusCode());
    }
}
