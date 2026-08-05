<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Demo\Services;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Synthetic demo sandbox storage metrics — never exposes host partition free space.
 */
final class DemoStorageQuotaService
{
    private const DEFAULT_QUOTA_BYTES = 2_147_483_648; // 2 GiB

    public function __construct(
        private DemoMode $demoMode,
    ) {
    }

    public function isActive(): bool
    {
        return $this->demoMode->isEnabled();
    }

    public function quotaBytes(): int
    {
        $raw = getenv('DEMO_STORAGE_QUOTA_BYTES') ?: ($_ENV['DEMO_STORAGE_QUOTA_BYTES'] ?? self::DEFAULT_QUOTA_BYTES);

        return max(104_857_600, (int) $raw);
    }

    /**
     * @return array{
     *     free_space: string,
     *     free_space_bytes: int,
     *     used_space_bytes: int,
     *     quota_bytes: int,
     *     demo_synthetic: true
     * }
     */
    public function metrics(string $storageRoot): array
    {
        $demoPath = rtrim($storageRoot, '/') . '/app/demo';
        $used = self::directorySizeBytes($demoPath);
        $quota = $this->quotaBytes();
        $free = max(0, $quota - $used);

        return [
            'free_space' => self::formatSize((float) $free),
            'free_space_bytes' => $free,
            'used_space_bytes' => $used,
            'quota_bytes' => $quota,
            'demo_synthetic' => true,
        ];
    }

    public static function directorySizeBytes(string $path): int
    {
        if (!is_dir($path)) {
            return 0;
        }

        $total = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $total += $file->getSize();
        }

        return $total;
    }

    public static function formatSize(float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
