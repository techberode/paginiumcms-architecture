<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Middleware;

use PaginiumCMS\Core\Cache\CacheDriverFactory;
use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Core\Cache\ContentCacheService;
use PaginiumCMS\Core\Cache\Drivers\FileDriver;
use PaginiumCMS\Core\Cache\Services\CacheAdminService;
use PaginiumCMS\Core\Cache\Services\CacheCapabilityProbe;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;
use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Notification\Services\IncidentNotifier;
use PaginiumCMS\Core\Performance\PerformanceBreachStore;
use PaginiumCMS\Core\Performance\PerformanceContext;
use PaginiumCMS\Core\Performance\PerformanceGuardPolicy;
use PaginiumCMS\Core\Performance\PerformanceGuardSettings;
use PaginiumCMS\Core\Performance\PerformanceIncidentService;
use PaginiumCMS\Core\Performance\PerformanceRouteLabelResolver;
use PaginiumCMS\Core\Performance\PerformanceSampleStore;
use PaginiumCMS\Core\Performance\SafeRemediationService;
use PaginiumCMS\Core\Security\SecurityLogger;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Http\Middleware\PerformanceGuardMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

final class PerformanceGuardMiddlewareTest extends TestCase
{
    private string $samplePath;

    private string $cacheDir;

    private PerformanceSampleStore $samples;

    protected function setUp(): void
    {
        parent::setUp();
        $this->samplePath = sys_get_temp_dir() . '/apm-mw-' . bin2hex(random_bytes(4)) . '.json';
        $this->cacheDir = sys_get_temp_dir() . '/apm-cache-' . bin2hex(random_bytes(4));
        mkdir($this->cacheDir, 0755, true);
        $this->samples = $this->createSampleStore();
    }

    protected function tearDown(): void
    {
        if (is_file($this->samplePath)) {
            unlink($this->samplePath);
        }
        foreach (glob($this->cacheDir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        @rmdir($this->cacheDir);

        parent::tearDown();
    }

    public function testDisabledGuardSkipsSampling(): void
    {
        $middleware = $this->makeMiddleware(['performanceGuardEnabled' => false]);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/health');
        $expected = (new ResponseFactory())->createResponse(200);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn($expected);

        $this->assertSame(200, $middleware->process($request, $handler)->getStatusCode());
        $this->assertSame([], $this->samples->all());
    }

    public function testEnabledGuardRecordsSample(): void
    {
        $middleware = $this->makeMiddleware(['performanceGuardEnabled' => true]);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/health');
        $expected = (new ResponseFactory())->createResponse(200);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn($expected);

        $middleware->process($request, $handler);

        $rows = $this->samples->all();
        $this->assertCount(1, $rows);
        $this->assertSame(200, $rows[0]['status']);
        $this->assertNotSame('', $rows[0]['route']);
    }

    /**
     * @param array<string, mixed> $engine
     */
    private function makeMiddleware(array $engine): PerformanceGuardMiddleware
    {
        $settingsRepo = $this->createMock(SettingsRepositoryInterface::class);
        $settingsRepo->method('group')->willReturnCallback(
            static fn (string $group): array => $group === 'engine' ? $engine : []
        );

        $settings = new PerformanceGuardSettings($settingsRepo);
        $policy = new PerformanceGuardPolicy($settings);
        $cache = new CacheManager(new FileDriver($this->cacheDir));

        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('exists')->willReturn(false);
        $writer = $this->createMock(FileWriterInterface::class);
        $breaches = new PerformanceBreachStore($reader, $writer);

        $cacheAdmin = new CacheAdminService($cache, new ContentCacheService($cache), $this->cacheDir);
        $logger = $this->createMock(LoggerInterface::class);

        $remediation = new SafeRemediationService(
            $settings,
            $cacheAdmin,
            new CacheCapabilityProbe(),
            new CacheDriverFactory($this->cacheDir),
            $settingsRepo,
            new SecurityLogger($logger),
            $breaches
        );

        $incidents = new PerformanceIncidentService(
            $settings,
            $breaches,
            $policy,
            new IncidentNotifier($settingsRepo, $this->createMock(NotificationService::class), $cache),
            $cache,
            $remediation
        );

        return new PerformanceGuardMiddleware(
            $settings,
            $policy,
            new PerformanceContext(),
            $this->samples,
            new PerformanceRouteLabelResolver(),
            $incidents,
            $cache
        );
    }

    private function createSampleStore(): PerformanceSampleStore
    {
        $writer = $this->createMock(FileWriterInterface::class);
        $writer->method('write')->willReturnCallback(function (string $relativePath, string $contents): void {
            file_put_contents($this->samplePath, $contents);
        });

        return new PerformanceSampleStore($writer, $this->samplePath);
    }
}
