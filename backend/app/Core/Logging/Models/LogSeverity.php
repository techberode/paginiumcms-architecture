<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Logging\Models;

/**
 * Enum priorít logovania.
 */
final class LogSeverity
{
    public const INFO = 'INFO';
    public const WARNING = 'WARNING';
    public const ERROR = 'ERROR';
    public const CRITICAL = 'CRITICAL';
    public const DEBUG = 'DEBUG';

    /**
     * Získa všetky dostupné priority.
     */
    public static function getAll(): array
    {
        return [
            self::DEBUG,
            self::INFO,
            self::WARNING,
            self::ERROR,
            self::CRITICAL,
        ];
    }

    /**
     * Zistí, či je priorita platná.
     */
    public static function isValid(string $severity): bool
    {
        return in_array($severity, self::getAll(), true);
    }

    /**
     * Získa farbu pre výpis.
     */
    public static function getColor(string $severity): string
    {
        return match ($severity) {
            self::DEBUG => 'gray',
            self::INFO => 'blue',
            self::WARNING => 'yellow',
            self::ERROR => 'red',
            self::CRITICAL => 'magenta',
            default => 'white',
        };
    }

    /**
     * Získa úroveň priority (čím vyššie, tým závažnejšie).
     */
    public static function getLevel(string $severity): int
    {
        return match ($severity) {
            self::DEBUG => 0,
            self::INFO => 1,
            self::WARNING => 2,
            self::ERROR => 3,
            self::CRITICAL => 4,
            default => 0,
        };
    }
}
