<?php

declare(strict_types=1);

use PaginiumCMS\Core\Backup\Services\BackupManager;
use PaginiumCMS\Core\Backup\Contracts\BackupInterface;
use PaginiumCMS\Core\Backup\Services\BackupScheduler;
use PaginiumCMS\Core\Backup\Commands\RunBackupScheduleCommand;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;

use function DI\create;
use function DI\get;

return [
    BackupInterface::class => create(BackupManager::class)
    ->constructor(
        get(FileReaderInterface::class),
                  get(FileWriterInterface::class),
                  __DIR__ . '/../../../storage/backups',
                  __DIR__ . '/../../../storage/app/content'
    ),
    BackupScheduler::class => create(BackupScheduler::class)
        ->constructor(get(BackupInterface::class)),
    RunBackupScheduleCommand::class => create(RunBackupScheduleCommand::class)
        ->constructor(get(BackupScheduler::class)),
];
