<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Webhooks;

/**
 * Canonical outbound webhook event names (It.80d).
 */
final class WebhookEventCatalog
{
    public const CONTENT_PUBLISHED = 'content.published';
    public const CONTENT_UPDATED = 'content.updated';
    public const TEST_PING = 'webhook.test';

    /** @var list<string> */
    public const ALL = [
        self::CONTENT_PUBLISHED,
        self::CONTENT_UPDATED,
    ];

    /** @var list<string> */
    public const ALL_WITH_TEST = [
        self::CONTENT_PUBLISHED,
        self::CONTENT_UPDATED,
        self::TEST_PING,
    ];

    public static function isValid(string $event): bool
    {
        return in_array($event, self::ALL_WITH_TEST, true);
    }

    public static function isSubscribable(string $event): bool
    {
        return in_array($event, self::ALL, true);
    }
}
