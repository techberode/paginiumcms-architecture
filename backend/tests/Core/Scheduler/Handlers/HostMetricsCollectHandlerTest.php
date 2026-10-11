<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Scheduler\Handlers;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\Performance\HostMetricsCollector;
use PaginiumCMS\Core\Performance\HostMetricsSettings;
use PaginiumCMS\Core\Performance\HostMetricsStore;
use PaginiumCMS\Core\Scheduler\Handlers\HostMetricsCollectHandler;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class HostMetricsCollectHandlerTest extends TestCase
{
    public function testSkipsWhenEngineSettingDisabled(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturn(['hostMetricsEnabled' => false]);

        $reader = $this->createMock(FileReaderInterface::class);
        $writer = $this->createMock(FileWriterInterface::class);

        $handler = new HostMetricsCollectHandler(
            new HostMetricsSettings($settings),
            new HostMetricsCollector(),
            new HostMetricsStore($reader, $writer)
        );

        $result = $handler->handle();
        $this->assertTrue($result->success);
        $this->assertStringContainsString('skipped', $result->message);
        $this->assertSame('host_metrics.collect', $handler->key());
    }

    public function testCollectsWhenEnabledOnLinux(): void
    {
        if (!is_readable('/proc/loadavg')) {
            $this->markTestSkipped('/proc/loadavg not available');
        }

        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnCallback(static fn (string $group): array => match ($group) {
            'engine' => ['hostMetricsEnabled' => true],
            default => [],
        });

        $reader = $this->createMock(FileReaderInterface::class);
        $writer = $this->createMock(FileWriterInterface::class);
        $writer->expects($this->once())->method('write');

        $handler = new HostMetricsCollectHandler(
            new HostMetricsSettings($settings),
            new HostMetricsCollector(),
            new HostMetricsStore($reader, $writer)
        );

        $result = $handler->handle();
        $this->assertTrue($result->success);
        $this->assertStringContainsString('saved', strtolower($result->message));
    }
}
