<?php

declare(strict_types=1);

namespace PaginiumCMS\Support;

/**
 * Centralizované nastavenie PHP časovej zóny pre logy, audit a date().
 */
final class AppTimezone
{
    public const DEFAULT = 'Europe/Bratislava';

    private static bool $dstEnabled = true;

    private static string $logicalTimezone = self::DEFAULT;

    public static function fromEnvironment(): string
    {
        $fromEnv = $_ENV['APP_TIMEZONE'] ?? $_SERVER['APP_TIMEZONE'] ?? null;

        if (is_string($fromEnv) && trim($fromEnv) !== '') {
            return trim($fromEnv);
        }

        return self::DEFAULT;
    }

    public static function apply(?string $timezone = null): bool
    {
        $candidate = $timezone ?? self::fromEnvironment();

        if ($candidate === '' || !in_array($candidate, timezone_identifiers_list(), true)) {
            return false;
        }

        date_default_timezone_set($candidate);

        return true;
    }

    public static function applyWithDst(string $timezone, bool $dstEnabled = true): bool
    {
        self::$logicalTimezone = trim($timezone) !== '' ? $timezone : self::DEFAULT;
        self::$dstEnabled = $dstEnabled;

        return self::apply(self::resolveTimezone(self::$logicalTimezone, $dstEnabled));
    }

    public static function resolveTimezone(string $timezone, bool $dstEnabled = true): string
    {
        if ($dstEnabled || trim($timezone) === '') {
            return $timezone;
        }

        try {
            $zone = new \DateTimeZone($timezone);
        } catch (\Exception) {
            return $timezone;
        }

        $year = (int) date('Y');
        $winter = new \DateTimeImmutable(sprintf('%d-01-15 12:00:00', $year), $zone);
        $standardOffset = $zone->getOffset($winter);

        return self::findFixedOffsetTimezone($standardOffset) ?? $timezone;
    }

    public static function isDaylightSavingActive(string $timezone, ?\DateTimeImmutable $at = null): bool
    {
        if (trim($timezone) === '') {
            return false;
        }

        try {
            $zone = new \DateTimeZone($timezone);
        } catch (\Exception) {
            return false;
        }

        $at ??= new \DateTimeImmutable('now', $zone);

        return (bool) (int) $at->format('I');
    }

    public static function current(): string
    {
        return date_default_timezone_get();
    }

    public static function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    public static function nowIso8601(): string
    {
        return date('c');
    }

    public static function isDstEnabled(): bool
    {
        return self::$dstEnabled;
    }

    public static function logicalTimezone(): string
    {
        return self::$logicalTimezone;
    }

    private static function findFixedOffsetTimezone(int $offsetSeconds): ?string
    {
        foreach (timezone_identifiers_list() as $identifier) {
            try {
                $zone = new \DateTimeZone($identifier);
            } catch (\Exception) {
                continue;
            }

            $january = new \DateTimeImmutable('2026-01-15 12:00:00', $zone);
            $july = new \DateTimeImmutable('2026-07-15 12:00:00', $zone);

            if ($zone->getOffset($january) === $offsetSeconds && $zone->getOffset($july) === $offsetSeconds) {
                return $identifier;
            }
        }

        return null;
    }
}
