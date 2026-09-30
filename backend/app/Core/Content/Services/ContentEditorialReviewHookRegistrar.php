<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Content\Services;

use PaginiumCMS\Core\Hook\HookCatalog;
use PaginiumCMS\Core\Hook\HookManager;

final class ContentEditorialReviewHookRegistrar
{
    public function __construct(
        private HookManager $hooks,
        private ContentEditorialReviewService $review,
        private ContentEditorialReviewNotificationService $notify,
    ) {
    }

    public function register(): void
    {
        $review = $this->review;
        $notify = $this->notify;

        $this->hooks->add(
            HookCatalog::CONTENT_AFTER_SAVE,
            static function (array $context) use ($review, $notify): void {
                $review->handleAfterSave($context);
                $notify->handleAfterSave($context);
            }
        );
    }
}
