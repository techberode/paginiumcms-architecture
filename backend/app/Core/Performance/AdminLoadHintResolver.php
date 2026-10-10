<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Performance;

/**
 * Iteration 100b — coarse instance load signal for deferring P2 admin fetches.
 *
 * @phpstan-type LoadHint array{level: 'normal'|'busy', reasons: list<string>}
 */
final class AdminLoadHintResolver
{
    private const SESSION_LOCK_BUSY_MS = 100.0;

    public function __construct(
        private PerformanceGuardSettings $settings,
        private PerformanceAggregator $aggregator,
        private PerformanceBreachStore $breaches,
    ) {
    }

    /**
     * @return LoadHint
     */
    public function resolve(): array
    {
        if (!$this->settings->enabled()) {
            return ['level' => 'normal', 'reasons' => []];
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

        $reasons = array_values(array_unique($reasons));

        return [
            'level' => $reasons === [] ? 'normal' : 'busy',
            'reasons' => $reasons,
        ];
    }
}
