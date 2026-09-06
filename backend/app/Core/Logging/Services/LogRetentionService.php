<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Logging\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\Logging\LogStoragePaths;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Purges old daily log files across all structured log sources.
 */
final class LogRetentionService
{
    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private SettingsRepositoryInterface $settings
    ) {
    }

    /**
     * @return array{app: int, audit: int, event: int, user: int, retention_days: int}
     */
    public function purgeOldLogs(?int $days = null): array
    {
        $logging = $this->settings->group('logging');
        $retentionDays = max(1, $days ?? (int) ($logging['retentionDays'] ?? 30));

        $app = 0;
        $audit = 0;
        $event = 0;
        $user = 0;

        foreach (LogStoragePaths::readerSources() as $source => $path) {
            $writer = new LogWriter($this->reader, $this->writer, $path);
            $removed = $writer->clearOld($retentionDays);
            match ($source) {
                'app' => $app = $removed,
                'audit' => $audit = $removed,
                'event' => $event = $removed,
                'user' => $user = $removed,
                default => null,
            };
        }

        return [
            'app' => $app,
            'audit' => $audit,
            'event' => $event,
            'user' => $user,
            'retention_days' => $retentionDays,
        ];
    }
}
