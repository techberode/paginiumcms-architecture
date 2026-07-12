<?php

declare(strict_types=1);

use PaginiumCMS\Core\Health\Services\HealthCheckManager;
use PaginiumCMS\Core\Health\Services\Checkers\SystemChecker;
use PaginiumCMS\Core\Health\Services\Checkers\StorageChecker;
use PaginiumCMS\Core\Health\Services\Checkers\CacheChecker;
use PaginiumCMS\Core\Health\Services\Checkers\SecurityChecker;
use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Http\Controllers\Admin\HealthController;

use function DI\create;
use function DI\get;

return [
    // Checkers
    SystemChecker::class => create(SystemChecker::class),
    StorageChecker::class => create(StorageChecker::class)
        ->constructor(__DIR__ . '/../../../storage'),
    CacheChecker::class => create(CacheChecker::class)
        ->constructor(get(CacheManager::class)),
    SecurityChecker::class => create(SecurityChecker::class),

    // Health Manager
    HealthCheckManager::class => create(HealthCheckManager::class)
        ->method('addChecks', [
            get(SystemChecker::class),
            get(StorageChecker::class),
            get(CacheChecker::class),
            get(SecurityChecker::class),
        ]),

    // Controller
    HealthController::class => create(HealthController::class)
        ->constructor(get(HealthCheckManager::class)),
];
