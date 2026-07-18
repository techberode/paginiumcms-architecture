<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Support;

use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Core\Cache\Drivers\MemoryDriver;
use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Notification\Services\IncidentNotifier;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

final class IncidentNotifierTestFactory
{
    public static function create(
        SettingsRepositoryInterface $settings,
        ?NotificationService $notifications = null
    ): IncidentNotifier {
        return new IncidentNotifier(
            $settings,
            $notifications ?? new NotificationService(),
            new CacheManager(new MemoryDriver(), 'test_')
        );
    }
}
