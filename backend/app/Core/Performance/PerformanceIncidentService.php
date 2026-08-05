<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Performance;

use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Core\Notification\Services\IncidentNotifier;

/**
 * Breach deduplication and incident dispatch for Performance Guard (Iteration 71).
 */
final class PerformanceIncidentService
{
    public function __construct(
        private PerformanceGuardSettings $settings,
        private PerformanceBreachStore $breaches,
        private PerformanceGuardPolicy $policy,
        private IncidentNotifier $incidents,
        private CacheManager $cache,
        private SafeRemediationService $remediation
    ) {
    }

    public function recordLatencyBreach(string $route, float $durationMs): void
    {
        if (!$this->settings->enabled()) {
            return;
        }

        $severity = $this->policy->severityForLatency($durationMs);
        if ($severity === null) {
            return;
        }

        $windowKey = sprintf(
            'perf:breach:%s:%s',
            md5($route),
            $severity
        );

        $count = $this->cache->increment($windowKey, 1, $this->settings->windowMinutes() * 60);
        if ($count < $this->settings->breachCount()) {
            return;
        }

        if ($this->breaches->findOpen($route, $severity) !== null) {
            return;
        }

        $breachId = bin2hex(random_bytes(8));
        $recommendations = $this->remediation->recommendations($route, $severity);
        $remediationResult = $this->remediation->maybeApplyAutomatic($route, $severity, $recommendations);

        $breach = [
            'id' => $breachId,
            'route' => $route,
            'severity' => $severity,
            'duration_ms' => round($durationMs, 2),
            'opened_at' => date('c'),
            'window_minutes' => $this->settings->windowMinutes(),
            'recommendations' => $recommendations,
            'remediation' => $remediationResult,
            'resolved_at' => null,
        ];

        $this->breaches->save($breach);

        $this->incidents->notify(
            'performance.guard',
            sprintf('Performance Guard %s on %s', $severity, $route),
            sprintf(
                'Route %s exceeded the %s latency budget (%.2f ms). Recommendations: %s',
                $route,
                $severity,
                $durationMs,
                implode('; ', $recommendations)
            ),
            $severity === 'critical' ? 'error' : 'warning'
        );
    }
}
