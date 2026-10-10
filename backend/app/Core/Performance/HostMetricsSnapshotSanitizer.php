<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Performance;

/**
 * Validates host metric ingest payloads (It.82d / It.46) — aggregate numbers only.
 */
final class HostMetricsSnapshotSanitizer
{
    /**
     * @param array<string, mixed> $raw
     * @return array<string, mixed>|null
     */
    public static function sanitize(array $raw): ?array
    {
        $collectedAt = self::normalizeCollectedAt($raw['collected_at'] ?? null);
        if ($collectedAt === null) {
            return null;
        }

        $snapshot = [
            'collected_at' => $collectedAt,
            'uptime_seconds' => self::nonNegativeInt($raw['uptime_seconds'] ?? null),
            'load' => self::load($raw['load'] ?? null),
            'memory' => self::memory($raw['memory'] ?? null),
            'disk' => self::disk($raw['disk'] ?? null),
        ];

        $cpu = self::optionalPercent($raw['cpu_percent'] ?? null);
        if ($cpu !== null) {
            $snapshot['cpu_percent'] = $cpu;
        }

        if ($snapshot['load'] === null && $snapshot['memory'] === null && $snapshot['disk'] === null) {
            return null;
        }

        return $snapshot;
    }

    private static function normalizeCollectedAt(mixed $value): ?string
    {
        if (is_int($value) && $value > 0) {
            return gmdate('c', $value);
        }

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $trimmed = trim($value);
        $ts = strtotime($trimmed);
        if ($ts === false) {
            return null;
        }

        return gmdate('c', $ts);
    }

    /**
     * @return array{1: float, 5: float, 15: float}|null
     */
    private static function load(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $one = self::nonNegativeFloat($value['1'] ?? $value[0] ?? null);
        $five = self::nonNegativeFloat($value['5'] ?? $value[1] ?? null);
        $fifteen = self::nonNegativeFloat($value['15'] ?? $value[2] ?? null);
        if ($one === null && $five === null && $fifteen === null) {
            return null;
        }

        return [
            '1' => $one ?? 0.0,
            '5' => $five ?? 0.0,
            '15' => $fifteen ?? 0.0,
        ];
    }

    /**
     * @return array{total_mb: int, used_mb: int, available_mb: int}|null
     */
    private static function memory(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $total = self::positiveInt($value['total_mb'] ?? null);
        $used = self::nonNegativeInt($value['used_mb'] ?? null);
        $available = self::nonNegativeInt($value['available_mb'] ?? null);
        if ($total === null || ($used === null && $available === null)) {
            return null;
        }

        if ($used === null) {
            $usedMb = max(0, $total - (int) $available);
            $availableMb = (int) $available;
        } elseif ($available === null) {
            $usedMb = $used;
            $availableMb = max(0, $total - $used);
        } else {
            $usedMb = $used;
            $availableMb = $available;
        }

        return [
            'total_mb' => $total,
            'used_mb' => $usedMb,
            'available_mb' => $availableMb,
        ];
    }

    /**
     * @return array{mount: string, total_gb: float, used_gb: float, available_gb: float, used_percent: float}|null
     */
    private static function disk(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $mount = is_string($value['mount'] ?? null) ? trim((string) $value['mount']) : '/';
        if ($mount === '' || strlen($mount) > 64 || str_contains($mount, '..')) {
            $mount = '/';
        }

        $total = self::nonNegativeFloat($value['total_gb'] ?? null);
        $used = self::nonNegativeFloat($value['used_gb'] ?? null);
        $available = self::nonNegativeFloat($value['available_gb'] ?? null);
        $percent = self::optionalPercent($value['used_percent'] ?? null);
        if ($total === null || $total <= 0.0) {
            return null;
        }

        return [
            'mount' => $mount,
            'total_gb' => $total,
            'used_gb' => $used ?? 0.0,
            'available_gb' => $available ?? max(0.0, $total - ($used ?? 0.0)),
            'used_percent' => $percent ?? min(100.0, (($used ?? 0.0) / $total) * 100.0),
        ];
    }

    private static function nonNegativeInt(mixed $value): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }

        $int = (int) round((float) $value);

        return $int >= 0 ? $int : null;
    }

    private static function positiveInt(mixed $value): ?int
    {
        $int = self::nonNegativeInt($value);

        return $int !== null && $int > 0 ? $int : null;
    }

    private static function nonNegativeFloat(mixed $value): ?float
    {
        if (!is_numeric($value)) {
            return null;
        }

        $float = (float) $value;

        return $float >= 0 ? round($float, 4) : null;
    }

    private static function optionalPercent(mixed $value): ?float
    {
        $float = self::nonNegativeFloat($value);
        if ($float === null || $float > 100.0) {
            return null;
        }

        return $float;
    }
}
