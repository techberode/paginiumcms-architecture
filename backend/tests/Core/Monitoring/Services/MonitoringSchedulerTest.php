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
use PaginiumCMS\Core\Logging\Contracts\LogWriterInterface;
use PaginiumCMS\Core\Monitoring\Services\FlatFileStatsCollector;
use PaginiumCMS\Core\Monitoring\Services\LogIncidentScanner;
use PaginiumCMS\Core\Monitoring\Services\MonitoringReportBuilder;
use PaginiumCMS\Core\Monitoring\Services\MonitoringReportScheduler;
use PaginiumCMS\Core\Monitoring\Services\MonitoringScheduler;
use PaginiumCMS\Core\Monitoring\Services\SchedulerStateStore;
use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Notification\Services\IncidentNotifier;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Tests\Support\IncidentNotifierTestFactory;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PHPUnit\Framework\TestCase;

final class MonitoringSchedulerTest extends TestCase
{
    public function testRunIfDueReturnsReportAndLogResults(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturn([
            'reportsEnabled' => false,
            'notifyLogErrors' => false,
            'notifyLogWarnings' => false,
        ]);

        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('exists')->willReturn(false);
        $fileWriter = $this->createMock(FileWriterInterface::class);

        $state = new SchedulerStateStore($reader, $fileWriter);
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
        $reportScheduler = new MonitoringReportScheduler(
            $settings,
            $builder,
            IncidentNotifierTestFactory::create($settings, $this->createMock(NotificationService::class)),
            $state
        );
        $logScanner = new LogIncidentScanner(
            $settings,
            $this->createMock(LogWriterInterface::class),
            IncidentNotifierTestFactory::create($settings, $this->createMock(NotificationService::class)),
            $state
        );

        $scheduler = new MonitoringScheduler($reportScheduler, $logScanner);
        $result = $scheduler->runIfDue();

        $this->assertArrayHasKey('report', $result);
        $this->assertArrayHasKey('logs', $result);
        $this->assertFalse($result['report']['sent']);
    }
}
