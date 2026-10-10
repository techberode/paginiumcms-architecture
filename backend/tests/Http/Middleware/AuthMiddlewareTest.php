<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Middleware;

use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthenticationInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\SessionManager;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

final class AuthMiddlewareTest extends TestCase
{
    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        parent::tearDown();
    }

    public function testGetRequestReleasesSessionWriteLockAfterAuth(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        session_start();
        $_SESSION = ['probe' => '1'];

        $session = new SessionManager();
        $auth = $this->authenticatedAuthMock();

        $middleware = new AuthMiddleware($auth, $session);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/pages');
        $response = $middleware->process($request, $this->handler());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($session->isWriteLockReleased());
    }

    public function testPostRequestKeepsSessionWriteLockAfterAuth(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        session_start();
        $_SESSION = ['probe' => '1'];

        $session = new SessionManager();
        $auth = $this->authenticatedAuthMock();

        $middleware = new AuthMiddleware($auth, $session);
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/api/pages');
        $response = $middleware->process($request, $this->handler());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($session->isWriteLockReleased());
    }

    public function testUnauthenticatedRequestDoesNotReleaseLock(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        session_start();
        $_SESSION = ['probe' => '1'];

        $session = new SessionManager();
        $auth = $this->createMock(AuthenticationInterface::class);
        $auth->method('isAuthenticated')->willReturn(false);

        $middleware = new AuthMiddleware($auth, $session);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/pages');
        $response = $middleware->process($request, $this->handler());

        $this->assertSame(401, $response->getStatusCode());
        $this->assertFalse($session->isWriteLockReleased());
    }

    private function authenticatedAuthMock(): AuthenticationInterface
    {
        $user = $this->createMock(User::class);

        $auth = $this->createMock(AuthenticationInterface::class);
        $auth->method('isAuthenticated')->willReturnCallback(function (): bool {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }

            return true;
        });
        $auth->method('getCurrentUser')->willReturn($user);
        $auth->expects($this->once())->method('touchSession');

        return $auth;
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
}
