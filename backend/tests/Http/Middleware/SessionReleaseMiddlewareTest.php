<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Middleware;

use PaginiumCMS\Http\Middleware\SessionReleaseMiddleware;
use PaginiumCMS\Modules\Security\Services\SessionManager;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

final class SessionReleaseMiddlewareTest extends TestCase
{
    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        parent::tearDown();
    }

    public function testGetRequestReleasesSessionWriteLock(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        session_start();
        $_SESSION = ['probe' => '1'];

        $session = new SessionManager();
        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
        $this->assertFalse($session->isWriteLockReleased());

        $middleware = new SessionReleaseMiddleware($session);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/pages');
        $response = $middleware->process($request, $this->handler());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($session->isWriteLockReleased());
    }

    public function testPostRequestKeepsSessionWriteLock(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        session_start();
        $_SESSION = ['probe' => '1'];

        $session = new SessionManager();
        $this->assertFalse($session->isWriteLockReleased());

        $middleware = new SessionReleaseMiddleware($session);
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/api/pages');
        $response = $middleware->process($request, $this->handler());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($session->isWriteLockReleased());
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
