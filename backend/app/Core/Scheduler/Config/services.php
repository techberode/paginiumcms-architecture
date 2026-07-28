<?php

declare(strict_types=1);

use PaginiumCMS\Core\Scheduler\Commands\ProcessWorkerCommand;
use PaginiumCMS\Core\Scheduler\Commands\RunJobCommand;
use PaginiumCMS\Core\Scheduler\Commands\RunSchedulerCommand;
use PaginiumCMS\Core\Scheduler\Handlers\BackupScheduledHandler;
use PaginiumCMS\Core\Scheduler\Handlers\ContentScheduledPublishHandler;
use PaginiumCMS\Core\Scheduler\Handlers\MonitoringPipelineHandler;
use PaginiumCMS\Core\Scheduler\Handlers\SystemDeployHandler;
use PaginiumCMS\Modules\Newsletter\Handlers\NewsletterWeeklyDigestHandler;
use PaginiumCMS\Core\Scheduler\Services\CronExpressionEvaluator;
use PaginiumCMS\Core\Scheduler\Services\JobHandlerRegistry;
use PaginiumCMS\Core\Scheduler\Services\JobQueueStore;
use PaginiumCMS\Core\Scheduler\Services\JobRegistryStore;
use PaginiumCMS\Core\Scheduler\Services\JobRunStore;
use PaginiumCMS\Core\Scheduler\Services\JobWorker;
use PaginiumCMS\Core\Scheduler\Services\ScheduledJobRunner;
use PaginiumCMS\Http\Controllers\Admin\JobsController;

use function DI\create;
use function DI\get;

return [
    CronExpressionEvaluator::class => create(CronExpressionEvaluator::class),
    JobRegistryStore::class => create(JobRegistryStore::class)
        ->constructor(
            get(\PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface::class),
            get(\PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface::class)
        ),
    JobRunStore::class => create(JobRunStore::class)
        ->constructor(
            get(\PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface::class),
            get(\PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface::class),
            get(JobRegistryStore::class)
        ),
    JobQueueStore::class => create(JobQueueStore::class)
        ->constructor(
            get(\PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface::class),
            get(\PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface::class)
        ),
    BackupScheduledHandler::class => create(BackupScheduledHandler::class)
        ->constructor(get(\PaginiumCMS\Core\Backup\Contracts\BackupInterface::class)),
    MonitoringPipelineHandler::class => create(MonitoringPipelineHandler::class)
        ->constructor(get(\PaginiumCMS\Core\Monitoring\Services\MonitoringScheduler::class)),
    ContentScheduledPublishHandler::class => create(ContentScheduledPublishHandler::class)
        ->constructor(get(\PaginiumCMS\Core\FlatFile\Services\ContentScheduledPublishService::class)),
    SystemDeployHandler::class => create(SystemDeployHandler::class)
        ->constructor(get(\PaginiumCMS\Core\SystemUpdate\Services\SystemDeployService::class)),
    NewsletterWeeklyDigestHandler::class => create(NewsletterWeeklyDigestHandler::class)
        ->constructor(get(\PaginiumCMS\Modules\Newsletter\Services\NewsletterMailService::class)),
    JobHandlerRegistry::class => create(JobHandlerRegistry::class)
        ->constructor(
            get(BackupScheduledHandler::class),
            get(MonitoringPipelineHandler::class),
            get(ContentScheduledPublishHandler::class),
            get(SystemDeployHandler::class),
            get(NewsletterWeeklyDigestHandler::class)
        ),
    ScheduledJobRunner::class => create(ScheduledJobRunner::class)
        ->constructor(
            get(\PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface::class),
            get(JobRegistryStore::class),
            get(JobRunStore::class),
            get(JobHandlerRegistry::class),
            get(CronExpressionEvaluator::class)
        ),
    JobWorker::class => create(JobWorker::class)
        ->constructor(
            get(JobQueueStore::class),
            get(ScheduledJobRunner::class)
        ),
    RunSchedulerCommand::class => create(RunSchedulerCommand::class)
        ->constructor(get(ScheduledJobRunner::class)),
    RunJobCommand::class => create(RunJobCommand::class)
        ->constructor(get(ScheduledJobRunner::class)),
    ProcessWorkerCommand::class => create(ProcessWorkerCommand::class)
        ->constructor(get(JobWorker::class)),
    JobsController::class => create(JobsController::class)
        ->constructor(
            get(\PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface::class),
            get(JobRegistryStore::class),
            get(JobRunStore::class),
            get(JobQueueStore::class),
            get(JobHandlerRegistry::class),
            get(ScheduledJobRunner::class),
            get(JobWorker::class),
            get(CronExpressionEvaluator::class),
            get(\PaginiumCMS\Http\Support\JsonResponder::class)
        ),
];
