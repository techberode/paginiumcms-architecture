<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Audit\Models;

/**
 * Enum závažnosti auditu.
 */
final class AuditSeverity
{
    public const CRITICAL = 'critical';
    public const ERROR = 'error';
    public const WARNING = 'warning';
    public const INFO = 'info';

    /**
     * Získa všetky dostupné závažnosti.
     *
     * @return array<int, string> Zoznam závažností.
     */
    public static function getAll(): array
    {
        return [
            self::CRITICAL,
            self::ERROR,
            self::WARNING,
            self::INFO,
        ];
    }

    /**
     * Zistí, či je závažnosť platná.
     *
     * @param string $severity Závažnosť.
     * @return bool TRUE ak je platná.
     */
    public static function isValid(string $severity): bool
    {
        return in_array($severity, self::getAll(), true);
    }

    /**
     * Získa úroveň závažnosti (čím vyššie číslo, tým závažnejšie).
     *
     * @param string $severity Závažnosť.
     * @return int Úroveň.
     */
    public static function getLevel(string $severity): int
    {
        return match ($severity) {
            self::CRITICAL => 4,
            self::ERROR => 3,
            self::WARNING => 2,
            self::INFO => 1,
            default => 0,
        };
    }
}
