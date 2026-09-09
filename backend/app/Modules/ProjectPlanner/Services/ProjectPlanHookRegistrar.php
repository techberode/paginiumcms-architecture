<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\ProjectPlanner\Services;

use PaginiumCMS\Core\Hook\HookCatalog;
use PaginiumCMS\Core\Hook\HookManager;

final class ProjectPlanHookRegistrar
{
    public function __construct(
        private HookManager $hooks,
        private ProjectPlanContentSyncService $sync,
    ) {
    }

    public function register(): void
    {
        $sync = $this->sync;

        $this->hooks->add(
            HookCatalog::CONTENT_AFTER_STATUS_CHANGE,
            static function (array $context) use ($sync): void {
                $sync->handleStatusChange($context);
            }
        );

        $this->hooks->add(
            HookCatalog::CONTENT_AFTER_SCHEDULED_PUBLISH,
            static function (array $context) use ($sync): void {
                $sync->handleScheduledPublish($context);
            }
        );

        $this->hooks->add(
            HookCatalog::CONTENT_AFTER_SAVE,
            static function (array $context) use ($sync): void {
                $sync->handleAfterSave($context);
            }
        );
    }
}
