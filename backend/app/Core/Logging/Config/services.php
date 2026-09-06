<?php

declare(strict_types=1);

use PaginiumCMS\Core\Logging\LogStoragePaths;
use PaginiumCMS\Core\Logging\Services\Logger;
use PaginiumCMS\Core\Logging\Services\LogWriter;
use PaginiumCMS\Core\Logging\Services\AuditLogger;
use PaginiumCMS\Core\Logging\Services\UserLogger;
use PaginiumCMS\Core\Logging\Services\EventLogger;
use PaginiumCMS\Core\Logging\Services\LogRetentionService;
use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;
use PaginiumCMS\Core\Logging\Contracts\LogWriterInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

use function DI\create;
use function DI\get;

return [
    // Log Writer
    LogWriterInterface::class => create(LogWriter::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class),
            LogStoragePaths::app()
        ),

    // Main Logger
    LoggerInterface::class => create(Logger::class)
        ->constructor(
            get(LogWriterInterface::class),
            'app',
            get(SettingsRepositoryInterface::class)
        ),

    // Audit Logger
    AuditLogger::class => create(AuditLogger::class)
        ->constructor(
            create(Logger::class)
                ->constructor(
                    create(LogWriter::class)
                        ->constructor(
                            get(FileReaderInterface::class),
                            get(FileWriterInterface::class),
                            LogStoragePaths::audit()
                        ),
                    'audit',
                    get(SettingsRepositoryInterface::class)
                )
        ),

    // User Logger
    UserLogger::class => create(UserLogger::class)
        ->constructor(
            create(Logger::class)
                ->constructor(
                    create(LogWriter::class)
                        ->constructor(
                            get(FileReaderInterface::class),
                            get(FileWriterInterface::class),
                            LogStoragePaths::user()
                        ),
                    'user',
                    get(SettingsRepositoryInterface::class)
                )
        ),

    // Event Logger
    EventLogger::class => create(EventLogger::class)
        ->constructor(
            create(Logger::class)
                ->constructor(
                    create(LogWriter::class)
                        ->constructor(
                            get(FileReaderInterface::class),
                            get(FileWriterInterface::class),
                            LogStoragePaths::event()
                        ),
                    'event',
                    get(SettingsRepositoryInterface::class)
                )
        ),

    LogRetentionService::class => create(LogRetentionService::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class),
            get(SettingsRepositoryInterface::class)
        ),
];