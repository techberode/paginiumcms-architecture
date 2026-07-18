<?php

declare(strict_types=1);

use PaginiumCMS\Core\Monitoring\Commands\RunMonitoringScheduleCommand;
use PaginiumCMS\Core\Monitoring\Services\FlatFileStatsCollector;
use PaginiumCMS\Core\Monitoring\Services\LogIncidentScanner;
use PaginiumCMS\Core\Monitoring\Services\MonitoringReportBuilder;
use PaginiumCMS\Core\Monitoring\Services\MonitoringReportScheduler;
use PaginiumCMS\Core\Monitoring\Services\MonitoringScheduler;
use PaginiumCMS\Core\Monitoring\Services\SchedulerStateStore;

use function DI\create;
use function DI\get;

return [
    SchedulerStateStore::class => create(SchedulerStateStore::class)
        ->constructor(
            get(\PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface::class),
            get(\PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface::class)
        ),
    FlatFileStatsCollector::class => create(FlatFileStatsCollector::class)
        ->constructor(
            get(\PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface::class),
            get(\PaginiumCMS\Modules\Security\Services\UserRepository::class),
            get(\PaginiumCMS\Core\Backup\Contracts\BackupInterface::class),
            get(\PaginiumCMS\Core\FlatFile\Services\TrashService::class),
            get(\PaginiumCMS\Core\Locking\Contracts\LockManagerInterface::class),
            get(\PaginiumCMS\Core\Conflict\Contracts\ConflictLoggerInterface::class)
        ),
    MonitoringReportBuilder::class => create(MonitoringReportBuilder::class)
        ->constructor(
            get(\PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface::class),
            get(\PaginiumCMS\Core\Analytics\Contracts\ReporterInterface::class),
            get(\PaginiumCMS\Core\Health\Services\HealthCheckManager::class),
            get(FlatFileStatsCollector::class)
        ),
    MonitoringReportScheduler::class => create(MonitoringReportScheduler::class)
        ->constructor(
            get(\PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface::class),
            get(MonitoringReportBuilder::class),
            get(\PaginiumCMS\Core\Notification\Services\IncidentNotifier::class),
            get(SchedulerStateStore::class)
        ),
    LogIncidentScanner::class => create(LogIncidentScanner::class)
        ->constructor(
            get(\PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface::class),
            get(\PaginiumCMS\Core\Logging\Contracts\LogWriterInterface::class),
            get(\PaginiumCMS\Core\Notification\Services\IncidentNotifier::class),
            get(SchedulerStateStore::class)
        ),
    MonitoringScheduler::class => create(MonitoringScheduler::class)
        ->constructor(
            get(MonitoringReportScheduler::class),
            get(LogIncidentScanner::class)
        ),
    RunMonitoringScheduleCommand::class => create(RunMonitoringScheduleCommand::class)
        ->constructor(get(MonitoringScheduler::class)),
];
