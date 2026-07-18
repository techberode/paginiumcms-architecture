<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Scheduler\Services;

use PaginiumCMS\Core\Backup\Contracts\BackupInterface;
use PaginiumCMS\Core\Analytics\Contracts\ReporterInterface;
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
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PaginiumCMS\Core\Scheduler\Handlers\BackupScheduledHandler;
use PaginiumCMS\Core\Scheduler\Handlers\MonitoringPipelineHandler;
use PaginiumCMS\Core\Scheduler\Services\CronExpressionEvaluator;
use PaginiumCMS\Core\Scheduler\Services\JobHandlerRegistry;
use PaginiumCMS\Core\Scheduler\Services\JobRegistryStore;
use PaginiumCMS\Core\Scheduler\Services\JobRunStore;
use PaginiumCMS\Core\Scheduler\Services\ScheduledJobRunner;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class ScheduledJobRunnerTest extends TestCase
{
    /** @var array<string, string> */
    private array $files = [];

    public function testRunDueSkipsWhenMasterSwitchOff(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->with('scheduler')->willReturn(['enabled' => false]);

        $runner = $this->makeRunner($settings);

        $result = $runner->runDue();
        $this->assertSame(0, $result['executed']);
    }

    public function testRunJobByIdExecutesBackupHandler(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $backup = $this->createMock(BackupInterface::class);
        $backup->method('runScheduledBackupIfDue')->willReturn(['ran' => false, 'reason' => 'not_due']);

        $runner = $this->makeRunner($settings, $backup);
        $result = $runner->runJobById('backup-scheduled');

        $this->assertSame('backup-scheduled', $result['job_id']);
        $this->assertSame('Backup not due', $result['message']);
        $this->assertFalse($result['success']);
    }

    private function makeRunner(
        SettingsRepositoryInterface $settings,
        ?BackupInterface $backup = null
    ): ScheduledJobRunner {
        [$registry, $runs] = $this->makeStores();

        $backup ??= $this->createMock(BackupInterface::class);

        $handlers = new JobHandlerRegistry(
            new BackupScheduledHandler($backup),
            new MonitoringPipelineHandler($this->buildMonitoringScheduler())
        );

        return new ScheduledJobRunner(
            $settings,
            $registry,
            $runs,
            $handlers,
            new CronExpressionEvaluator()
        );
    }

    /**
     * @return array{0: JobRegistryStore, 1: JobRunStore}
     */
    private function makeStores(): array
    {
        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('exists')->willReturnCallback(fn (string $path): bool => isset($this->files[$path]));
        $reader->method('read')->willReturnCallback(fn (string $path): string => $this->files[$path] ?? '');

        $writer = $this->createMock(FileWriterInterface::class);
        $writer->method('write')->willReturnCallback(function (string $path, string $content): void {
            $this->files[$path] = $content;
        });

        $registry = new JobRegistryStore($reader, $writer);
        $runs = new JobRunStore($reader, $writer, $registry);

        return [$registry, $runs];
    }

    private function buildMonitoringScheduler(): MonitoringScheduler
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
            new IncidentNotifier($settings, $this->createMock(NotificationService::class)),
            $state
        );
        $logScanner = new LogIncidentScanner(
            $settings,
            $this->createMock(LogWriterInterface::class),
            new IncidentNotifier($settings, $this->createMock(NotificationService::class)),
            $state
        );

        return new MonitoringScheduler($reportScheduler, $logScanner);
    }
}
