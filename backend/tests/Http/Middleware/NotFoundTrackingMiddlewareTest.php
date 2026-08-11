<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Middleware;

use PaginiumCMS\Core\Seo\Services\NotFoundHitStore;
use PaginiumCMS\Http\Middleware\NotFoundTrackingMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use org\bovigo\vfs\vfsStream;

final class NotFoundTrackingMiddlewareTest extends TestCase
{
    public function testRecords404OnPublicPath(): void
    {
        vfsStream::setup('root', null, ['data' => ['metrics' => []]]);
        $root = vfsStream::url('root');
        $validator = new \PaginiumCMS\Core\FlatFile\Services\FileValidator($root);
        $reader = new \PaginiumCMS\Core\FlatFile\Services\FileReader($validator);
        $store = new NotFoundHitStore($reader);

        $middleware = new NotFoundTrackingMiddleware($store);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/ghost-page');

        $response404 = (new ResponseFactory())->createResponse(404);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response404);

        $middleware->process($request, $handler);

        $top = $store->topPaths(7, 5);
        $this->assertCount(1, $top);
        $this->assertSame('/ghost-page', $top[0]['path']);
    }

    public function testSkipsAdmin404(): void
    {
        vfsStream::setup('root', null, ['data' => ['metrics' => []]]);
        $root = vfsStream::url('root');
        $validator = new \PaginiumCMS\Core\FlatFile\Services\FileValidator($root);
        $reader = new \PaginiumCMS\Core\FlatFile\Services\FileReader($validator);
        $store = new NotFoundHitStore($reader);

        $middleware = new NotFoundTrackingMiddleware($store);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/admin/missing');

        $response404 = (new ResponseFactory())->createResponse(404);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response404);

        $middleware->process($request, $handler);

        $this->assertSame([], $store->topPaths(7, 5));
    }
}
