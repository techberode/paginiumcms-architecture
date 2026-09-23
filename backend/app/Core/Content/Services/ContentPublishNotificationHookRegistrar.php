<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Content\Services;

use PaginiumCMS\Core\Hook\HookCatalog;
use PaginiumCMS\Core\Hook\HookManager;

final class ContentPublishNotificationHookRegistrar
{
    public function __construct(
        private HookManager $hooks,
        private ContentPublishNotificationService $notifications,
    ) {
    }

    public function register(): void
    {
        $service = $this->notifications;

        $this->hooks->add(
            HookCatalog::CONTENT_AFTER_SCHEDULED_PUBLISH,
            static function (array $context) use ($service): void {
                $service->handleScheduledPublishHook($context);
            }
        );

        $this->hooks->add(
            HookCatalog::CONTENT_AFTER_STATUS_CHANGE,
            static function (array $context) use ($service): void {
                $service->handleStatusChangeHook($context);
            }
        );
    }
}
