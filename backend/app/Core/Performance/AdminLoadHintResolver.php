<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Performance;

/**
 * Iteration 100b — coarse instance load signal for deferring P2 admin fetches.
 *
 * @phpstan-type LoadHint array{level: 'normal'|'busy', reasons: list<string>, active_admin_sessions: int}
 */
final class AdminLoadHintResolver
{
    private const SESSION_LOCK_BUSY_MS = 100.0;

    public function __construct(
        private PerformanceGuardSettings $settings,
        private PerformanceAggregator $aggregator,
        private PerformanceBreachStore $breaches,
        private HostMetricsService $hostMetrics,
        private HostMetricsSettings $hostMetricsSettings,
        private AdminActiveSessionStore $activeSessions,
        private AdminLoadHintSettings $loadHintSettings,
    ) {
    }

    /**
     * @return LoadHint
     */
    public function resolve(): array
    {
        $activeCount = $this->activeSessions->countActive();

        if (!$this->settings->enabled()) {
            $reasons = [];
            $this->appendHostLoadReason($reasons);
            $this->appendActiveSessionsReason($reasons, $activeCount);

            return [
                'level' => $reasons === [] ? 'normal' : 'busy',
                'reasons' => array_values(array_unique($reasons)),
                'active_admin_sessions' => $activeCount,
            ];
        }

        $reasons = [];
        $summary = $this->aggregator->summary(3);

        $p95 = $summary['p95_ms'];
        $warning = (float) $this->settings->latencyMsWarning();
        if ($p95 !== null && $p95 >= $warning) {
            $reasons[] = 'apm_p95';
        }

        $sessionLockP95 = $summary['session_lock_ms_p95'] ?? null;
        if ($sessionLockP95 !== null && $sessionLockP95 >= self::SESSION_LOCK_BUSY_MS) {
            $reasons[] = 'session_lock_p95';
        }

        $openBreaches = 0;
        foreach ($this->breaches->recent(15) as $breach) {
            if (($breach['resolved_at'] ?? null) !== null) {
                continue;
            }
            $openBreaches++;
            if (($breach['severity'] ?? '') === 'critical') {
                $reasons[] = 'apm_breach_critical';
                break;
            }
        }

        if ($openBreaches >= 2 && !in_array('apm_breach_critical', $reasons, true)) {
            $reasons[] = 'apm_breach_open';
        }

        $this->appendHostLoadReason($reasons);
        $this->appendActiveSessionsReason($reasons, $activeCount);

        $reasons = array_values(array_unique($reasons));

        return [
            'level' => $reasons === [] ? 'normal' : 'busy',
            'reasons' => $reasons,
            'active_admin_sessions' => $activeCount,
        ];
    }

    /**
     * @param array<string> $reasons
     */
    private function appendHostLoadReason(array &$reasons): void
    {
        if (!$this->hostMetricsSettings->enabled()) {
            return;
        }

        $threshold = $this->hostMetricsSettings->loadHintBusyThreshold();
        if ($threshold <= 0.0) {
            return;
        }

        $view = $this->hostMetrics->publicView();
        if ($view['status'] !== 'ok' || !is_array($view['snapshot'])) {
            return;
        }

        $load = $view['snapshot']['load'] ?? null;
        if (!is_array($load)) {
            return;
        }

        $load1 = (float) ($load['1'] ?? 0.0);
        if ($load1 >= $threshold) {
            $reasons[] = 'host_load_1m';
        }
    }

    /**
     * @param array<string> $reasons
     */
    private function appendActiveSessionsReason(array &$reasons, int $activeCount): void
    {
        $threshold = $this->loadHintSettings->activeSessionsBusyThreshold();
        if ($threshold <= 0) {
            return;
        }

        if ($activeCount >= $threshold) {
            $reasons[] = 'admin_active_sessions';
        }
    }
}
