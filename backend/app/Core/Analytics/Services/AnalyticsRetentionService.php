<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Analytics\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Support\JsonHelper;

/**
 * Purges analytics flat-file data older than configured retention.
 */
final class AnalyticsRetentionService
{
    public function __construct(
        private FileReaderInterface $reader,
        private SettingsRepositoryInterface $settings,
        private string $storagePath = 'data/analytics'
    ) {
    }

    /**
     * @return array{visits: int, daily: int, visitors: int, retention_days: int}
     */
    public function purgeOldData(?int $days = null): array
    {
        $analytics = $this->settings->group('analytics');
        $retentionDays = max(1, $days ?? (int) ($analytics['retentionDays'] ?? 90));
        $cutoff = strtotime(sprintf('-%d days', $retentionDays));
        if ($cutoff === false) {
            $cutoff = time() - ($retentionDays * 86400);
        }

        return [
            'visits' => $this->purgeDatedJsonFiles('visits', $cutoff),
            'daily' => $this->purgeDatedJsonFiles('daily', $cutoff),
            'visitors' => $this->purgeStaleVisitors($cutoff),
            'retention_days' => $retentionDays,
        ];
    }

    private function purgeDatedJsonFiles(string $subdir, int $cutoffTimestamp): int
    {
        $dir = $this->absolutePath($subdir);
        if (!is_dir($dir)) {
            return 0;
        }

        $deleted = 0;
        foreach ($this->listJsonFiles($dir) as $file) {
            $dateStr = pathinfo($file, PATHINFO_FILENAME);
            $fileTime = strtotime((string) $dateStr);
            if ($fileTime !== false && $fileTime < $cutoffTimestamp) {
                if (@unlink($file)) {
                    ++$deleted;
                }
            }
        }

        return $deleted;
    }

    private function purgeStaleVisitors(int $cutoffTimestamp): int
    {
        $dir = $this->absolutePath('visitors');
        if (!is_dir($dir)) {
            return 0;
        }

        $deleted = 0;
        foreach ($this->listJsonFiles($dir) as $file) {
            $raw = @file_get_contents($file);
            if (!is_string($raw) || trim($raw) === '') {
                if (@unlink($file)) {
                    ++$deleted;
                }
                continue;
            }

            try {
                $data = JsonHelper::decode($raw);
            } catch (\Throwable) {
                if (@unlink($file)) {
                    ++$deleted;
                }
                continue;
            }

            $lastVisit = strtotime((string) ($data['lastVisit'] ?? ''));
            if ($lastVisit === false || $lastVisit < $cutoffTimestamp) {
                if (@unlink($file)) {
                    ++$deleted;
                }
            }
        }

        return $deleted;
    }

    /**
     * @return list<string>
     */
    private function listJsonFiles(string $dir): array
    {
        $entries = scandir($dir);
        if ($entries === false) {
            return [];
        }

        $files = [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..' || !str_ends_with($entry, '.json')) {
                continue;
            }
            $files[] = $dir . '/' . $entry;
        }

        return $files;
    }

    private function absolutePath(string $subdir): string
    {
        return rtrim($this->reader->getBasePath(), '/')
            . '/'
            . trim($this->storagePath, '/')
            . '/'
            . trim($subdir, '/');
    }
}
