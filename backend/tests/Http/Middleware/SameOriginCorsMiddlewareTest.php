<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Middleware;

use PaginiumCMS\Http\Middleware\SameOriginCorsMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

final class SameOriginCorsMiddlewareTest extends TestCase
{
    public function testAllowsSameOriginPostWhenNotInStaticAllowList(): void
    {
        $middleware = new SameOriginCorsMiddleware([
            'origin' => ['https://paginiumcms.com'],
            'methods' => ['GET', 'POST', 'OPTIONS'],
            'headers.allow' => ['Content-Type', 'X-CSRF-TOKEN'],
            'credentials' => true,
            'origin.server' => 'https://paginiumcms.com',
        ]);

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', 'https://demo.paginiumcms.com/api/auth/login')
            ->withHeader('Host', 'demo.paginiumcms.com')
            ->withHeader('Origin', 'https://demo.paginiumcms.com')
            ->withHeader('X-Forwarded-Proto', 'https')
            ->withHeader('Content-Type', 'application/json');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn((new ResponseFactory())->createResponse(200));

        $response = $middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            'https://demo.paginiumcms.com',
            $response->getHeaderLine('Access-Control-Allow-Origin')
        );
    }

    public function testReturnsJsonWhenCrossOriginIsRejected(): void
    {
        $middleware = new SameOriginCorsMiddleware([
            'origin' => ['https://paginiumcms.com'],
            'methods' => ['GET', 'POST', 'OPTIONS'],
            'headers.allow' => ['Content-Type'],
            'credentials' => true,
            'origin.server' => 'https://paginiumcms.com',
        ]);

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', 'https://demo.paginiumcms.com/api/auth/login')
            ->withHeader('Host', 'demo.paginiumcms.com')
            ->withHeader('Origin', 'https://evil.example')
            ->withHeader('X-Forwarded-Proto', 'https');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $response = $middleware->process($request, $handler);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
        $payload = json_decode((string) $response->getBody(), true);
        $this->assertSame(false, $payload['success'] ?? null);
        $this->assertSame('cors_rejected', $payload['code'] ?? null);
    }

    public function testPreflightSameOriginReturns200(): void
    {
        $middleware = new SameOriginCorsMiddleware([
            'origin' => ['https://paginiumcms.com'],
            'methods' => ['GET', 'POST', 'OPTIONS'],
            'headers.allow' => ['Content-Type', 'X-CSRF-TOKEN'],
            'credentials' => true,
            'origin.server' => 'https://paginiumcms.com',
            'cache' => 86400,
        ]);

        $request = (new ServerRequestFactory())
            ->createServerRequest('OPTIONS', 'https://demo.paginiumcms.com/api/auth/login')
            ->withHeader('Host', 'demo.paginiumcms.com')
            ->withHeader('Origin', 'https://demo.paginiumcms.com')
            ->withHeader('X-Forwarded-Proto', 'https')
            ->withHeader('Access-Control-Request-Method', 'POST')
            ->withHeader('Access-Control-Request-Headers', 'content-type,x-csrf-token');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $response = $middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            'https://demo.paginiumcms.com',
            $response->getHeaderLine('Access-Control-Allow-Origin')
        );
    }
}
