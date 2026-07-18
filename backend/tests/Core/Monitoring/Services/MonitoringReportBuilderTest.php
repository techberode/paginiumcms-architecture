<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Monitoring\Services;

use PaginiumCMS\Core\Analytics\Contracts\ReporterInterface;
use PaginiumCMS\Core\Backup\Contracts\BackupInterface;
use PaginiumCMS\Core\Conflict\Contracts\ConflictLoggerInterface;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Services\TrashService;
use PaginiumCMS\Core\Health\Services\HealthCheckManager;
use PaginiumCMS\Core\Locking\Contracts\LockManagerInterface;
use PaginiumCMS\Core\Monitoring\Services\FlatFileStatsCollector;
use PaginiumCMS\Core\Monitoring\Services\MonitoringReportBuilder;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PHPUnit\Framework\TestCase;

final class MonitoringReportBuilderTest extends TestCase
{
    public function testBuildReturnsHtmlEmailDocument(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnCallback(static function (string $group): array {
            return match ($group) {
                'general' => ['siteName' => 'Test Site'],
                'monitoring' => [
                    'reportIncludeAnalytics' => true,
                    'reportIncludeHealth' => false,
                    'reportIncludeFlatFile' => false,
                ],
                default => [],
            };
        });

        $reporter = $this->createMock(ReporterInterface::class);
        $reporter->method('getAggregatedOverview')->willReturn([
            'visits' => 12,
            'page_views' => 34,
            'unique_visitors' => 8,
            'realtime_visitors' => 2,
        ]);
        $reporter->method('getTopPages')->willReturn([
            ['uri' => '/blog', 'views' => 10],
        ]);
        $reporter->method('getTopIpStats')->willReturn([]);
        $reporter->method('getTopArticles')->willReturn([]);
        $reporter->method('getTopReferers')->willReturn([]);
        $reporter->method('getDeviceStats')->willReturn([
            'mobile' => 2,
            'desktop' => 8,
            'tablet' => 0,
            'unknown' => 0,
        ]);

        $health = $this->createMock(HealthCheckManager::class);
        $flatFile = new FlatFileStatsCollector(
            $this->createMock(ContentRepositoryInterface::class),
            $this->createMock(UserRepository::class),
            $this->createMock(BackupInterface::class),
            $this->createMock(TrashService::class),
            $this->createMock(LockManagerInterface::class),
            $this->createMock(ConflictLoggerInterface::class)
        );

        $builder = new MonitoringReportBuilder($settings, $reporter, $health, $flatFile);
        $payload = $builder->build('day');

        $this->assertStringContainsString('[Test Site] Monitoring report (day)', $payload['subject']);
        $this->assertStringContainsString('Návštevy: 12', $payload['body']);
        $this->assertStringContainsString('<!DOCTYPE html>', $payload['html']);
        $this->assertStringContainsString('PaginiumCMS Monitoring', $payload['html']);
        $this->assertStringContainsString('/blog', $payload['html']);
        $this->assertStringContainsString('Prehľad návštevnosti', $payload['html']);
        $this->assertStringContainsString('background:#070b14', $payload['html']);
        $this->assertSame(['analytics'], $payload['sections']);
    }
}
