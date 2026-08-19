<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Analytics\Middleware;

use PaginiumCMS\Core\Analytics\Contracts\ReporterInterface;
use PaginiumCMS\Core\Analytics\Contracts\TrackerInterface;
use PaginiumCMS\Core\Analytics\Middleware\AnalyticsMiddleware;
use PaginiumCMS\Core\Analytics\Services\AnalyticsManager;
use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Core\Cache\Drivers\MemoryDriver;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

final class AnalyticsMiddlewareTest extends TestCase
{
    public function testStoragePathsAreNotTracked(): void
    {
        $tracker = $this->createMock(TrackerInterface::class);
        $tracker->expects($this->never())->method('track');

        $analytics = new AnalyticsManager(
            $tracker,
            $this->createMock(ReporterInterface::class),
            $this->createMock(SettingsRepositoryInterface::class),
            new CacheManager(new MemoryDriver())
        );

        $middleware = new AnalyticsMiddleware($analytics);
        $request = (new ServerRequestFactory())->createServerRequest(
            'GET',
            '/storage/app/content/media/hero.png'
        );

        $response = $middleware->process($request, $this->handler());

        $this->assertSame(200, $response->getStatusCode());
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
