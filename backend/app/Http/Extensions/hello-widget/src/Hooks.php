<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Extensions\HelloWidget;

/**
 * Reference hook handlers shipped with PaginiumCMS (Wave 5d).
 */
final class Hooks
{
    /** @var array<string, mixed>|null */
    public static ?array $lastContentContext = null;

    public static bool $booted = false;

    /**
     * @param array<string, mixed> $context
     */
    public static function onBoot(array $context): void
    {
        self::$booted = true;
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function onContentAfterSave(array $context): void
    {
        self::$lastContentContext = $context;
    }

    public static function reset(): void
    {
        self::$booted = false;
        self::$lastContentContext = null;
    }
}
