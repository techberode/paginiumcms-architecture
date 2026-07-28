<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Newsletter\Services;

use PaginiumCMS\Core\Hook\HookCatalog;
use PaginiumCMS\Core\Hook\HookManager;

final class NewsletterHookRegistrar
{
    public function __construct(
        private HookManager $hooks,
        private NewsletterMailService $mailService
    ) {
    }

    public function register(): void
    {
        $mail = $this->mailService;

        $this->hooks->add(
            HookCatalog::CONTENT_AFTER_STATUS_CHANGE,
            static function (array $context) use ($mail): void {
                $mail->handleContentStatusChange($context);
            }
        );

        $this->hooks->add(
            HookCatalog::CONTENT_AFTER_SCHEDULED_PUBLISH,
            static function (array $context) use ($mail): void {
                $mail->handleScheduledPublish($context);
            }
        );
    }
}
