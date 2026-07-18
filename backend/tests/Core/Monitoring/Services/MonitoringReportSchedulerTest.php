<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Monitoring\Services;

use PaginiumCMS\Core\Analytics\Contracts\ReporterInterface;
use PaginiumCMS\Core\Backup\Contracts\BackupInterface;
use PaginiumCMS\Core\Conflict\Contracts\ConflictLoggerInterface;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Services\TrashService;
use PaginiumCMS\Core\Health\Services\HealthCheckManager;
use PaginiumCMS\Core\Locking\Contracts\LockManagerInterface;
use PaginiumCMS\Core\Monitoring\Services\FlatFileStatsCollector;
use PaginiumCMS\Core\Monitoring\Services\MonitoringReportBuilder;
use PaginiumCMS\Core\Monitoring\Services\MonitoringReportScheduler;
use PaginiumCMS\Core\Monitoring\Services\SchedulerStateStore;
use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Notification\Services\IncidentNotifier;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Tests\Support\IncidentNotifierTestFactory;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PHPUnit\Framework\TestCase;

final class MonitoringReportSchedulerTest extends TestCase
{
    public function testIsDueReturnsFalseWhenReportsDisabled(): void
    {
        $scheduler = $this->makeScheduler(['reportsEnabled' => false]);

        $this->assertFalse($scheduler->isDue());
    }

    public function testRunIfDueReturnsNotDueWhenDisabled(): void
    {
        $scheduler = $this->makeScheduler(['reportsEnabled' => false]);

        $this->assertSame(['sent' => false, 'connector' => '', 'reason' => 'not_due'], $scheduler->runIfDue());
    }

    public function testRunIfDueForceUsesNotifier(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnCallback(static function (string $group): array {
            if ($group === 'monitoring') {
                return [
                    'reportsEnabled' => false,
                    'reportInterval' => 'day',
                    'reportConnector' => 'email',
                    'reportIncludeAnalytics' => false,
                    'reportIncludeHealth' => false,
                    'reportIncludeFlatFile' => false,
                ];
            }

            return ['siteName' => 'Test', 'timezone' => 'UTC', 'adminEmail' => 'admin@example.com'];
        });

        $reporter = $this->createMock(ReporterInterface::class);
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

        $notifications = $this->createMock(NotificationService::class);
        $notifications->method('getAdapters')->willReturn(['email']);
        $notifications->expects($this->once())->method('send')->willReturn(true);

        $scheduler = new MonitoringReportScheduler(
            $settings,
            $builder,
            IncidentNotifierTestFactory::create($settings, $notifications),
            $this->makeStateStore()
        );

        $result = $scheduler->runIfDue(true);

        $this->assertTrue($result['sent']);
        $this->assertSame('email', $result['connector']);
    }

    /**
     * @param array<string, mixed> $monitoring
     */
    private function makeScheduler(array $monitoring): MonitoringReportScheduler
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnCallback(static function (string $group) use ($monitoring): array {
            if ($group === 'monitoring') {
                return $monitoring;
            }

            return ['timezone' => 'UTC'];
        });

        $reporter = $this->createMock(ReporterInterface::class);
        $health = $this->createMock(HealthCheckManager::class);
        $flatFile = new FlatFileStatsCollector(
            $this->createMock(ContentRepositoryInterface::class),
            $this->createMock(UserRepository::class),
            $this->createMock(BackupInterface::class),
            $this->createMock(TrashService::class),
            $this->createMock(LockManagerInterface::class),
            $this->createMock(ConflictLoggerInterface::class)
        );

        return new MonitoringReportScheduler(
            $settings,
            new MonitoringReportBuilder($settings, $reporter, $health, $flatFile),
            IncidentNotifierTestFactory::create($settings, $this->createMock(NotificationService::class)),
            $this->makeStateStore()
        );
    }

    private function makeStateStore(): SchedulerStateStore
    {
        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('exists')->willReturn(false);
        $writer = $this->createMock(FileWriterInterface::class);

        return new SchedulerStateStore($reader, $writer);
    }
}
