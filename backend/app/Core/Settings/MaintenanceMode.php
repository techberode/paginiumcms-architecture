<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Settings;

/**
 * Pomocník pre režimy údržby — vždy je aktívny najviac jeden režim.
 */
final class MaintenanceMode
{
    public const OFF = 'off';
    public const COMING_SOON = 'coming_soon';
    public const UNDER_MAINTENANCE = 'under_maintenance';

    /** @var list<string> */
    public const MODES = [
        self::OFF,
        self::COMING_SOON,
        self::UNDER_MAINTENANCE,
    ];

    /**
     * @param array<string, mixed> $maintenance
     */
    public static function resolve(array $maintenance): string
    {
        $mode = (string) ($maintenance['mode'] ?? self::OFF);

        return in_array($mode, self::MODES, true) ? $mode : self::OFF;
    }

    /**
     * @param array<string, mixed> $maintenance
     */
    public static function isActive(array $maintenance): bool
    {
        return self::resolve($maintenance) !== self::OFF;
    }
}
