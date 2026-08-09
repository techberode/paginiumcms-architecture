<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Middleware;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\Seo\Services\RedirectStore;
use PaginiumCMS\Http\Middleware\RedirectMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use org\bovigo\vfs\vfsStream;

final class RedirectMiddlewareTest extends TestCase
{
    private RedirectStore $store;

    protected function setUp(): void
    {
        vfsStream::setup('root', null, ['data' => []]);
        $root = vfsStream::url('root');
        $validator = new FileValidator($root);
        $reader = new FileReader($validator);
        $this->store = new RedirectStore($reader);
    }

    public function testRedirectsPublicPath(): void
    {
        $this->store->create('/old', '/new', 301);

        $middleware = new RedirectMiddleware($this->store);
        $request = (new ServerRequestFactory())->createServerRequest('GET', 'https://example.test/old');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $response = $middleware->process($request, $handler);

        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('/new', $response->getHeaderLine('Location'));
    }

    public function testSkipsApiPaths(): void
    {
        $this->store->create('/api/pages', '/elsewhere', 301);

        $middleware = new RedirectMiddleware($this->store);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/pages/home');

        $expected = (new ResponseFactory())->createResponse(200);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn($expected);

        $this->assertSame(200, $middleware->process($request, $handler)->getStatusCode());
    }

    public function testPassesThroughOnMiss(): void
    {
        $middleware = new RedirectMiddleware($this->store);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/missing');

        $expected = (new ResponseFactory())->createResponse(200);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn($expected);

        $this->assertSame(200, $middleware->process($request, $handler)->getStatusCode());
    }
}
