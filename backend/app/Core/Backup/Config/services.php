<?php

declare(strict_types=1);

use PaginiumCMS\Core\Backup\Services\BackupManager;
use PaginiumCMS\Core\Backup\Contracts\BackupInterface;
use PaginiumCMS\Core\Backup\Services\BackupScheduler;
use PaginiumCMS\Core\Backup\Commands\RunBackupScheduleCommand;
use PaginiumCMS\Core\Backup\Services\BackupOffsiteWebDavService;
use PaginiumCMS\Core\Cache\ContentCacheService;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

use function DI\create;
use function DI\get;

return [
    BackupInterface::class => create(BackupManager::class)
    ->constructor(
        get(FileReaderInterface::class),
        get(FileWriterInterface::class),
        __DIR__ . '/../../../storage/backups',
        __DIR__ . '/../../../storage/app/content',
        get(ContentCacheService::class),
        get(ContentIndexService::class),
        get(ContentRepositoryInterface::class),
        get(BackupOffsiteWebDavService::class),
        get(SettingsRepositoryInterface::class),
    ),
    BackupScheduler::class => create(BackupScheduler::class)
        ->constructor(get(BackupInterface::class)),
    RunBackupScheduleCommand::class => create(RunBackupScheduleCommand::class)
        ->constructor(get(BackupScheduler::class)),
];
