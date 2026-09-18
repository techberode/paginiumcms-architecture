<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Extensions\HelloWidget;

use PaginiumCMS\Http\Extensions\Capabilities\PluginCapabilityCatalog;
use PaginiumCMS\Http\Extensions\Capabilities\PluginRuntimeContext;

/**
 * Reference hook handlers shipped with PaginiumCMS (Wave 5d / It.89b).
 */
final class Hooks
{
    /** @var array<string, mixed>|null */
    public static ?array $lastContentContext = null;

    public static ?PluginRuntimeContext $lastRuntime = null;

    /** @var array<string, mixed>|null */
    public static ?array $lastReadDocument = null;

    public static bool $booted = false;

    /**
     * @param array<string, mixed> $context
     */
    public static function onBoot(array $context, ?PluginRuntimeContext $runtime = null): void
    {
        self::$booted = true;
        self::$lastRuntime = $runtime;
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function onContentAfterSave(array $context, ?PluginRuntimeContext $runtime = null): void
    {
        self::$lastContentContext = $context;
        self::$lastRuntime = $runtime;
        self::$lastReadDocument = null;

        if ($runtime === null || !$runtime->can(PluginCapabilityCatalog::CONTENT_READ)) {
            return;
        }

        $type = is_string($context['type'] ?? null) ? $context['type'] : 'page';
        $slug = is_string($context['slug'] ?? null) ? trim($context['slug']) : '';
        if ($slug === '') {
            return;
        }

        self::$lastReadDocument = $runtime->content()->get($type, $slug);
    }

    public static function reset(): void
    {
        self::$booted = false;
        self::$lastContentContext = null;
        self::$lastRuntime = null;
        self::$lastReadDocument = null;
    }
}
