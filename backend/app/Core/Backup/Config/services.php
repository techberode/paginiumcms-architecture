<?php

declare(strict_types=1);

use PaginiumCMS\Core\Backup\Services\BackupManager;
use PaginiumCMS\Core\Backup\Contracts\BackupInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;

use function DI\create;
use function DI\get;

return [
    // Mapovanie rozhrania na implementáciu
    BackupInterface::class => create(BackupManager::class)
    ->constructor(
        get(FileReaderInterface::class),
                  get(FileWriterInterface::class),
                  __DIR__ . '/../../../storage/backups',
                  __DIR__ . '/../../../storage/app/content'
    ),
];
