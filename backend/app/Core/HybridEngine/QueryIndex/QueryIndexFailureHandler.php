<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\HybridEngine\QueryIndex;

use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Core\Notification\Services\IncidentNotifier;
use PaginiumCMS\Core\Security\SecurityLogger;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Throttled alerts (and optional driver revert) when SQLite projection fails (It.92).
 */
final class QueryIndexFailureHandler
{
    private const DEFAULT_COOLDOWN_SECONDS = 900;

    public function __construct(
        private SettingsRepositoryInterface $settings,
        private QueryIndexRuntimeWatch $watch,
        private IncidentNotifier $incidents,
        private CacheManager $cache,
        private SecurityLogger $securityLogger
    ) {
    }

    public function handleDetectedIssue(?string $issue = null): void
    {
        if (!$this->watch->isWatchActive()) {
            return;
        }

        $issue ??= $this->watch->detectIssue();
        if ($issue === null) {
            return;
        }

        $this->dispatch($issue);
    }

    public function handleQueryFallback(): void
    {
        if (!$this->watch->isWatchActive()) {
            return;
        }

        $this->dispatch(QueryIndexRuntimeWatch::ISSUE_QUERY_FAILED);
    }

    private function dispatch(string $issue): void
    {
        $engine = $this->settings->group('engine');
        $cooldown = max(
            60,
            (int) ($engine['queryIndexFailureAlertCooldownSeconds'] ?? self::DEFAULT_COOLDOWN_SECONDS)
        );
        $dedupeKey = 'query_index.failure.' . $issue;
        if (!$this->shouldSendThrottled($dedupeKey, $cooldown)) {
            return;
        }

        $message = $this->watch->humanMessage($issue);
        $this->incidents->notify(
            'query_index.sqlite_failure',
            'SQLite query index degraded',
            $message . ' Flat-file JSON catalog remains authoritative; the public site should stay up.',
            'warning'
        );

        $autoFallback = ($engine['queryIndexAutoFallbackOnFailure'] ?? false) === true;
        if ($autoFallback) {
            $this->settings->setGroup('engine', ['queryIndexDriver' => QueryIndexInterface::DRIVER_JSON]);
            $this->securityLogger->logSuspiciousActivity(
                'query_index.auto_fallback',
                sprintf('Reverted queryIndexDriver to json after issue: %s', $issue)
            );
            $this->incidents->notify(
                'query_index.auto_fallback',
                'Query index driver reverted to JSON',
                'engine.queryIndexDriver was set back to json automatically. Rebuild SQLite and re-enable when ready.',
                'info'
            );
        }
    }

    private function shouldSendThrottled(string $dedupeKey, int $cooldownSeconds): bool
    {
        $cacheKey = 'incident.cooldown.' . md5($dedupeKey);
        if ($this->cache->has($cacheKey)) {
            return false;
        }

        $this->cache->set($cacheKey, time(), $cooldownSeconds);

        return true;
    }
}
