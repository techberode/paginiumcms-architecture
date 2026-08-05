<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Support;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\Git\Services\GitPathValidator;
use PaginiumCMS\Core\Git\Services\GitPublishDispatcher;
use PaginiumCMS\Core\Git\Services\GitPublishService;
use PaginiumCMS\Core\Git\Services\GitPublishSettings;
use PaginiumCMS\Core\Git\Services\LocalGitProcess;
use PaginiumCMS\Core\Git\Services\LocalGitPublisher;
use PaginiumCMS\Core\Git\Services\PublishPlanner;
use PaginiumCMS\Core\Git\Services\PublishQueueStore;
use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;
use PaginiumCMS\Core\Logging\Models\LogEntry;
use PaginiumCMS\Core\Scheduler\Handlers\GitPublishHandler;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Builds real Git publish objects with distribution disabled (final classes are not mockable).
 */
final class GitPublishTestHelper
{
    public static function disabledDispatcher(
        FileReaderInterface $reader,
        FileWriterInterface $writer,
        SettingsRepositoryInterface $settings,
    ): GitPublishDispatcher {
        return new GitPublishDispatcher(self::disabledService($reader, $writer, $settings));
    }

    public static function disabledHandler(
        FileReaderInterface $reader,
        FileWriterInterface $writer,
        SettingsRepositoryInterface $settings,
    ): GitPublishHandler {
        return new GitPublishHandler(self::disabledService($reader, $writer, $settings));
    }

    public static function disabledService(
        FileReaderInterface $reader,
        FileWriterInterface $writer,
        SettingsRepositoryInterface $settings,
    ): GitPublishService {
        $gitSettings = new GitPublishSettings($settings);
        $queue = new PublishQueueStore($reader, $writer);
        $planner = new PublishPlanner($settings);
        $publisher = new LocalGitPublisher($settings, new LocalGitProcess(), new GitPathValidator());

        return new GitPublishService(
            $gitSettings,
            $queue,
            $planner,
            $publisher,
            new GitPathValidator(),
            self::noopLogger(),
        );
    }

    public static function noopLogger(): LoggerInterface
    {
        return new class implements LoggerInterface {
            public function info(string $message, array $context = []): void
            {
            }

            public function warning(string $message, array $context = []): void
            {
            }

            public function error(string $message, array $context = []): void
            {
            }

            public function critical(string $message, array $context = []): void
            {
            }

            public function debug(string $message, array $context = []): void
            {
            }

            public function log(string $severity, string $message, array $context = []): void
            {
            }

            public function writeEntry(LogEntry $entry): void
            {
            }

            public function getLastEntries(int $limit = 100): array
            {
                return [];
            }

            public function getEntriesBySeverity(string $severity, int $limit = 100): array
            {
                return [];
            }

            public function getEntriesByCategory(string $category, int $limit = 100): array
            {
                return [];
            }

            public function clearOldEntries(int $days = 30): int
            {
                return 0;
            }
        };
    }
}
