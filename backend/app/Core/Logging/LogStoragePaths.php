<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Logging;

/**
 * Jednotná cesta k flat-file logom (zapisuje aj číta ApplicationLogReader / LogWriter).
 */
final class LogStoragePaths
{
    public static function base(): string
    {
        return dirname(__DIR__, 2) . '/storage/logs';
    }

    public static function app(): string
    {
        return self::base() . '/app';
    }

    public static function audit(): string
    {
        return self::base() . '/audit';
    }

    public static function event(): string
    {
        return self::base() . '/event';
    }

    public static function user(): string
    {
        return self::base() . '/user';
    }

    /**
     * @return array<string, string>
     */
    public static function readerSources(): array
    {
        return [
            'app' => self::app(),
            'audit' => self::audit(),
            'event' => self::event(),
            'user' => self::user(),
        ];
    }
}
