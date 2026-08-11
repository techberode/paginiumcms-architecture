<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Webhooks\Services;

use PaginiumCMS\Core\Hook\HookCatalog;
use PaginiumCMS\Core\Hook\HookManager;
use PaginiumCMS\Core\Webhooks\WebhookEventCatalog;

/**
 * Maps content lifecycle hooks to outbound webhook events (It.80d).
 */
final class WebhookHookRegistrar
{
    public function __construct(
        private HookManager $hooks,
        private OutboundWebhookDispatcher $dispatcher,
    ) {
    }

    public function register(): void
    {
        $dispatcher = $this->dispatcher;

        $this->hooks->add(
            HookCatalog::CONTENT_AFTER_STATUS_CHANGE,
            static function (array $context) use ($dispatcher): void {
                $status = (string) ($context['status'] ?? '');
                $previousStatus = (string) ($context['previousStatus'] ?? '');

                if ($status === 'published' && $previousStatus !== 'published') {
                    $dispatcher->dispatch(WebhookEventCatalog::CONTENT_PUBLISHED, self::contentPayload($context));
                }
            }
        );

        $this->hooks->add(
            HookCatalog::CONTENT_AFTER_SCHEDULED_PUBLISH,
            static function (array $context) use ($dispatcher): void {
                $dispatcher->dispatch(WebhookEventCatalog::CONTENT_PUBLISHED, self::contentPayload($context));
            }
        );

        $this->hooks->add(
            HookCatalog::CONTENT_AFTER_SAVE,
            static function (array $context) use ($dispatcher): void {
                $status = (string) ($context['status'] ?? '');
                $action = (string) ($context['action'] ?? '');

                if ($status !== 'published') {
                    return;
                }

                if ($action === 'create') {
                    $dispatcher->dispatch(WebhookEventCatalog::CONTENT_PUBLISHED, self::contentPayload($context));

                    return;
                }

                // Status transitions are handled by CONTENT_AFTER_STATUS_CHANGE.
                if ($action === 'update') {
                    $dispatcher->dispatch(WebhookEventCatalog::CONTENT_UPDATED, self::contentPayload($context));
                }
            }
        );
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private static function contentPayload(array $context): array
    {
        return [
            'type' => (string) ($context['type'] ?? ''),
            'slug' => (string) ($context['slug'] ?? ''),
            'status' => (string) ($context['status'] ?? ''),
            'action' => (string) ($context['action'] ?? ''),
            'userId' => (string) ($context['userId'] ?? ''),
        ];
    }
}
