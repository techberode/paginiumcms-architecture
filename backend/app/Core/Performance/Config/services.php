<?php

declare(strict_types=1);

use PaginiumCMS\Core\Cache\CacheDriverFactory;
use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Core\Cache\Services\CacheAdminService;
use PaginiumCMS\Core\Cache\Services\CacheCapabilityProbe;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\Notification\Services\IncidentNotifier;
use PaginiumCMS\Core\Performance\PerformanceAggregator;
use PaginiumCMS\Core\Performance\PerformanceBreachStore;
use PaginiumCMS\Core\Performance\PerformanceContext;
use PaginiumCMS\Core\Performance\PerformanceGuardPolicy;
use PaginiumCMS\Core\Performance\PerformanceGuardSettings;
use PaginiumCMS\Core\Performance\PerformanceIncidentService;
use PaginiumCMS\Core\Performance\PerformanceRouteLabelResolver;
use PaginiumCMS\Core\Performance\PerformanceSampleStore;
use PaginiumCMS\Core\Performance\SafeRemediationService;
use PaginiumCMS\Core\Security\SecurityLogger;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Http\Controllers\Admin\MetricsController;
use PaginiumCMS\Http\Middleware\PerformanceGuardMiddleware;
use PaginiumCMS\Http\Support\JsonResponder;
use function DI\create;
use function DI\get;

return [
    PerformanceContext::class => create(PerformanceContext::class),
    PerformanceGuardSettings::class => create(PerformanceGuardSettings::class)
        ->constructor(get(SettingsRepositoryInterface::class)),
    PerformanceGuardPolicy::class => create(PerformanceGuardPolicy::class)
        ->constructor(get(PerformanceGuardSettings::class)),
    PerformanceRouteLabelResolver::class => create(PerformanceRouteLabelResolver::class),
    PerformanceSampleStore::class => function ($container) {
        $validator = $container->get(FileValidator::class);

        return new PerformanceSampleStore(
            $container->get(FileWriterInterface::class),
            $validator->getAbsolutePath('data/metrics/apm-samples.json')
        );
    },
    PerformanceBreachStore::class => create(PerformanceBreachStore::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class)
        ),
    PerformanceAggregator::class => create(PerformanceAggregator::class)
        ->constructor(get(PerformanceSampleStore::class)),
    SafeRemediationService::class => create(SafeRemediationService::class)
        ->constructor(
            get(PerformanceGuardSettings::class),
            get(CacheAdminService::class),
            get(CacheCapabilityProbe::class),
            get(CacheDriverFactory::class),
            get(SettingsRepositoryInterface::class),
            get(SecurityLogger::class),
            get(PerformanceBreachStore::class)
        ),
    PerformanceIncidentService::class => create(PerformanceIncidentService::class)
        ->constructor(
            get(PerformanceGuardSettings::class),
            get(PerformanceBreachStore::class),
            get(PerformanceGuardPolicy::class),
            get(IncidentNotifier::class),
            get(CacheManager::class),
            get(SafeRemediationService::class)
        ),
    PerformanceGuardMiddleware::class => create(PerformanceGuardMiddleware::class)
        ->constructor(
            get(PerformanceGuardSettings::class),
            get(PerformanceGuardPolicy::class),
            get(PerformanceContext::class),
            get(PerformanceSampleStore::class),
            get(PerformanceRouteLabelResolver::class),
            get(PerformanceIncidentService::class),
            get(CacheManager::class)
        ),
    MetricsController::class => create(MetricsController::class)
        ->constructor(
            get(PerformanceGuardSettings::class),
            get(PerformanceAggregator::class),
            get(PerformanceBreachStore::class),
            get(PerformanceSampleStore::class),
            get(JsonResponder::class)
        ),
];
