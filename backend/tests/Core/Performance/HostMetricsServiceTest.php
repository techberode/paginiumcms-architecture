<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Performance;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\Performance\HostMetricsService;
use PaginiumCMS\Core\Performance\HostMetricsSettings;
use PaginiumCMS\Core\Performance\HostMetricsStore;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class HostMetricsServiceTest extends TestCase
{
    public function testPublicViewReportsStaleSnapshot(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnCallback(static fn (string $group): array => match ($group) {
            'engine' => [
                'hostMetricsEnabled' => true,
                'hostMetricsMaxAgeSeconds' => 60,
            ],
            default => [],
        });

        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('exists')->willReturn(true);
        $reader->method('read')->willReturn(json_encode([
            'collected_at' => gmdate('c', time() - 3600),
            'load' => ['1' => 0.1, '5' => 0.1, '15' => 0.1],
        ], JSON_THROW_ON_ERROR));

        $writer = $this->createMock(FileWriterInterface::class);
        $store = new HostMetricsStore($reader, $writer);
        $service = new HostMetricsService(new HostMetricsSettings($settings), $store);

        $view = $service->publicView();
        $this->assertSame('stale', $view['status']);
        $this->assertTrue($view['enabled']);
    }
}
