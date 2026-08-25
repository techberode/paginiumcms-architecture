<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Middleware;

use PaginiumCMS\Core\Performance\PerformanceContext;
use PaginiumCMS\Core\Performance\PerformanceGuardSettings;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Http\Middleware\ServerTimingMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

final class ServerTimingMiddlewareTest extends TestCase
{
    public function testDisabledSkipsHeader(): void
    {
        $middleware = new ServerTimingMiddleware(
            $this->settings(['performanceGuardServerTiming' => false]),
            new PerformanceContext()
        );

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/health');
        $response = (new ResponseFactory())->createResponse(200);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);

        $result = $middleware->process($request, $handler);

        $this->assertSame('', $result->getHeaderLine('Server-Timing'));
    }

    public function testEnabledAddsHeader(): void
    {
        $context = new PerformanceContext();
        $context->recordStorageReadDuration(5_000_000);
        $context->recordSessionLockDuration(2_000_000);

        $middleware = new ServerTimingMiddleware(
            $this->settings(['performanceGuardServerTiming' => true]),
            $context
        );

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/health');
        $response = (new ResponseFactory())->createResponse(200);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);

        $result = $middleware->process($request, $handler);
        $header = $result->getHeaderLine('Server-Timing');

        $this->assertStringContainsString('sess-lock;dur=2.00', $header);
        $this->assertStringContainsString('storage;dur=5.00', $header);
        $this->assertStringContainsString('app;dur=', $header);
    }

    /**
     * @param array<string, mixed> $engine
     */
    private function settings(array $engine): PerformanceGuardSettings
    {
        $repository = $this->createMock(SettingsRepositoryInterface::class);
        $repository->method('group')->willReturnCallback(
            static fn (string $group): array => $group === 'engine' ? $engine : []
        );

        return new PerformanceGuardSettings($repository);
    }
}
