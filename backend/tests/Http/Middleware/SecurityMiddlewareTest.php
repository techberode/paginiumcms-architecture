<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Middleware;

use PaginiumCMS\Http\Middleware\SecurityMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

final class SecurityMiddlewareTest extends TestCase
{
    public function testAppliesSecurityHeaders(): void
    {
        $middleware = new SecurityMiddleware();
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $inner = (new ResponseFactory())->createResponse(200);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($inner);

        $response = $middleware->process($request, $handler);

        $this->assertStringContainsString('max-age=', $response->getHeaderLine('Strict-Transport-Security'));
        $this->assertStringContainsString("default-src 'self'", $response->getHeaderLine('Content-Security-Policy'));
        $this->assertSame('DENY', $response->getHeaderLine('X-Frame-Options'));
        $this->assertSame('1; mode=block', $response->getHeaderLine('X-XSS-Protection'));
        $this->assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $response->getHeaderLine('Referrer-Policy'));
        $this->assertStringContainsString('geolocation=()', $response->getHeaderLine('Permissions-Policy'));
    }

    public function testRemovesSensitiveServerHeaders(): void
    {
        $middleware = new SecurityMiddleware(['remove_server_headers' => true]);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $inner = (new ResponseFactory())->createResponse(200)
            ->withHeader('Server', 'Apache')
            ->withHeader('X-Powered-By', 'PHP/8.5');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($inner);

        $response = $middleware->process($request, $handler);

        $this->assertFalse($response->hasHeader('Server'));
        $this->assertFalse($response->hasHeader('X-Powered-By'));
    }

    public function testCustomCspConfigIsApplied(): void
    {
        $middleware = new SecurityMiddleware([
            'csp_default' => "default-src 'none'",
            'csp_script' => "script-src 'none'",
            'csp_style' => "style-src 'none'",
            'csp_img' => "img-src 'none'",
            'csp_font' => "font-src 'none'",
            'csp_connect' => "connect-src 'none'",
        ]);

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $inner = (new ResponseFactory())->createResponse(204);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($inner);

        $response = $middleware->process($request, $handler);

        $this->assertSame(
            "default-src 'none'; script-src 'none'; style-src 'none'; img-src 'none'; font-src 'none'; connect-src 'none'",
            $response->getHeaderLine('Content-Security-Policy')
        );
    }
}
