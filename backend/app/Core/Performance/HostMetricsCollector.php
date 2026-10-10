<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Performance;

/**
 * Linux-friendly host snapshot for cron/CLI (no shell exec).
 */
final class HostMetricsCollector
{
    /**
     * @return array<string, mixed>|null
     */
    public function collect(?string $diskPath = null): ?array
    {
        $diskPath = $diskPath ?? $this->defaultDiskPath();
        $load = $this->readLoad();
        $memory = $this->readMemory();
        $disk = $this->readDisk($diskPath);
        if ($load === null && $memory === null && $disk === null) {
            return null;
        }

        $snapshot = [
            'collected_at' => gmdate('c'),
            'uptime_seconds' => $this->readUptimeSeconds(),
            'load' => $load,
            'memory' => $memory,
            'disk' => $disk,
        ];

        return HostMetricsSnapshotSanitizer::sanitize($snapshot);
    }

    private function defaultDiskPath(): string
    {
        $backendRoot = dirname(__DIR__, 3);

        return is_dir($backendRoot) ? $backendRoot : '/';
    }

    /**
     * @return array{1: float, 5: float, 15: float}|null
     */
    private function readLoad(): ?array
    {
        $raw = @file_get_contents('/proc/loadavg');
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }

        $parts = preg_split('/\s+/', trim($raw));
        if (!is_array($parts) || count($parts) < 3) {
            return null;
        }

        return [
            '1' => (float) $parts[0],
            '5' => (float) $parts[1],
            '15' => (float) $parts[2],
        ];
    }

    /**
     * @return array{total_mb: int, used_mb: int, available_mb: int}|null
     */
    private function readMemory(): ?array
    {
        $raw = @file_get_contents('/proc/meminfo');
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        $totalKb = $this->meminfoValue($raw, 'MemTotal');
        $availableKb = $this->meminfoValue($raw, 'MemAvailable');
        if ($totalKb === null) {
            return null;
        }

        if ($availableKb === null) {
            $freeKb = $this->meminfoValue($raw, 'MemFree') ?? 0;
            $buffersKb = $this->meminfoValue($raw, 'Buffers') ?? 0;
            $cachedKb = $this->meminfoValue($raw, 'Cached') ?? 0;
            $availableKb = $freeKb + $buffersKb + $cachedKb;
        }

        $totalMb = (int) round($totalKb / 1024);
        $availableMb = (int) round($availableKb / 1024);

        return [
            'total_mb' => $totalMb,
            'used_mb' => max(0, $totalMb - $availableMb),
            'available_mb' => max(0, $availableMb),
        ];
    }

    private function meminfoValue(string $raw, string $key): ?int
    {
        if (preg_match('/^' . preg_quote($key, '/') . ':\s+(\d+)\s+kB$/m', $raw, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    /**
     * @return array{mount: string, total_gb: float, used_gb: float, available_gb: float, used_percent: float}|null
     */
    private function readDisk(string $path): ?array
    {
        $total = @disk_total_space($path);
        $free = @disk_free_space($path);
        if (!is_float($total) || !is_float($free) || $total <= 0) {
            return null;
        }

        $used = max(0.0, $total - $free);
        $toGb = static fn (float $bytes): float => round($bytes / 1024 / 1024 / 1024, 2);

        return [
            'mount' => $path,
            'total_gb' => $toGb($total),
            'used_gb' => $toGb($used),
            'available_gb' => $toGb($free),
            'used_percent' => round(($used / $total) * 100, 1),
        ];
    }

    private function readUptimeSeconds(): int
    {
        $raw = @file_get_contents('/proc/uptime');
        if (!is_string($raw) || $raw === '') {
            return 0;
        }

        $parts = explode(' ', trim($raw));

        return is_numeric($parts[0]) ? (int) floor((float) $parts[0]) : 0;
    }
}
