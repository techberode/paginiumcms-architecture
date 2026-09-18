<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\HybridEngine\QueryIndex;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Detects SQLite query index runtime problems while driver is set to sqlite (It.92).
 */
final class QueryIndexRuntimeWatch
{
    public const ISSUE_PDO_UNAVAILABLE = 'pdo_unavailable';
    public const ISSUE_FILE_MISSING = 'file_missing';
    public const ISSUE_INTEGRITY_FAILED = 'integrity_failed';
    public const ISSUE_CATALOG_LAG = 'catalog_lag';
    public const ISSUE_QUERY_FAILED = 'query_failed';

    public function __construct(
        private SettingsRepositoryInterface $settings,
        private QueryIndexCapabilityProbe $probe,
        private QueryIndexPaths $paths,
        private QueryIndexSqliteStore $store
    ) {
    }

    public function isWatchActive(): bool
    {
        $engine = $this->settings->group('engine');

        return ($engine['queryIndexDriver'] ?? QueryIndexInterface::DRIVER_JSON) === QueryIndexInterface::DRIVER_SQLITE
            && ($engine['queryIndexRuntimeWatchEnabled'] ?? true) === true;
    }

    public function detectIssue(): ?string
    {
        if (!$this->isWatchActive()) {
            return null;
        }

        if (!extension_loaded('pdo_sqlite')) {
            return self::ISSUE_PDO_UNAVAILABLE;
        }

        if (!is_readable($this->paths->absolutePath())) {
            return self::ISSUE_FILE_MISSING;
        }

        if (!$this->store->integrityOk()) {
            return self::ISSUE_INTEGRITY_FAILED;
        }

        if (!$this->probe->parityWithJson()) {
            return self::ISSUE_CATALOG_LAG;
        }

        return null;
    }

    public function humanMessage(string $issue): string
    {
        return match ($issue) {
            self::ISSUE_PDO_UNAVAILABLE => 'pdo_sqlite is not available; catalog reads fall back to JSON.',
            self::ISSUE_FILE_MISSING => 'content.sqlite is missing or unreadable; catalog reads fall back to JSON.',
            self::ISSUE_INTEGRITY_FAILED => 'SQLite integrity check failed; rebuild the derived index.',
            self::ISSUE_CATALOG_LAG => 'SQLite entry count does not match JSON catalog; rebuild recommended.',
            self::ISSUE_QUERY_FAILED => 'A catalog query failed against SQLite; request served from JSON fallback.',
            default => 'SQLite query index runtime issue detected.',
        };
    }
}
