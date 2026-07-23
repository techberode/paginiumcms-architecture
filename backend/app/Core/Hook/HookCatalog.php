<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Hook;

/**
 * Canonical hook names emitted by Core (Wave 5d / It.15 completion).
 *
 * Extensions subscribe via plugin.json → hooks map.
 */
final class HookCatalog
{
    public const CONTENT_BEFORE_SAVE = 'content.before_save';
    public const CONTENT_AFTER_SAVE = 'content.after_save';
    public const CONTENT_AFTER_DELETE = 'content.after_delete';
    public const CONTENT_AFTER_STATUS_CHANGE = 'content.after_status_change';
    public const CONTENT_AFTER_SCHEDULED_PUBLISH = 'content.after_scheduled_publish';

    public const EXTENSION_BOOT = 'extension.boot';
    public const EXTENSION_ENABLED = 'extension.enabled';
    public const EXTENSION_DISABLED = 'extension.disabled';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::CONTENT_BEFORE_SAVE,
            self::CONTENT_AFTER_SAVE,
            self::CONTENT_AFTER_DELETE,
            self::CONTENT_AFTER_STATUS_CHANGE,
            self::CONTENT_AFTER_SCHEDULED_PUBLISH,
            self::EXTENSION_BOOT,
            self::EXTENSION_ENABLED,
            self::EXTENSION_DISABLED,
        ];
    }

    public static function isRegistered(string $hook): bool
    {
        return in_array($hook, self::all(), true);
    }

    /**
     * @return array<string, array{description: string, payload: list<string>}>
     */
    public static function describe(): array
    {
        return [
            self::CONTENT_BEFORE_SAVE => [
                'description' => 'Before content is persisted (create/update).',
                'payload' => ['type', 'slug', 'status', 'action', 'userId'],
            ],
            self::CONTENT_AFTER_SAVE => [
                'description' => 'After content was saved successfully.',
                'payload' => ['type', 'slug', 'status', 'action', 'userId'],
            ],
            self::CONTENT_AFTER_DELETE => [
                'description' => 'After content was deleted (soft or hard).',
                'payload' => ['type', 'slug', 'userId'],
            ],
            self::CONTENT_AFTER_STATUS_CHANGE => [
                'description' => 'After status changed (manual API or OTP publish).',
                'payload' => ['type', 'slug', 'status', 'previousStatus', 'userId'],
            ],
            self::CONTENT_AFTER_SCHEDULED_PUBLISH => [
                'description' => 'After cron job auto-published scheduled content.',
                'payload' => ['type', 'slug', 'scheduledAt'],
            ],
            self::EXTENSION_BOOT => [
                'description' => 'During application boot for each enabled extension.',
                'payload' => ['id', 'manifest'],
            ],
            self::EXTENSION_ENABLED => [
                'description' => 'When admin enables an extension.',
                'payload' => ['id', 'manifest'],
            ],
            self::EXTENSION_DISABLED => [
                'description' => 'When admin disables an extension.',
                'payload' => ['id'],
            ],
        ];
    }
}
