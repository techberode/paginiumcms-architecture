<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Performance;

/**
 * Latency budget evaluation per route group (Iteration 71).
 */
final class PerformanceGuardPolicy
{
    public function __construct(
        private PerformanceGuardSettings $settings
    ) {
    }

    public function severityForLatency(float $durationMs): ?string
    {
        if ($durationMs >= $this->settings->latencyMsCritical()) {
            return 'critical';
        }

        if ($durationMs >= $this->settings->latencyMsWarning()) {
            return 'warning';
        }

        return null;
    }

    public function shouldSample(): bool
    {
        $rate = $this->settings->sampleRate();
        if ($rate >= 1.0) {
            return true;
        }

        if ($rate <= 0.0) {
            return false;
        }

        return mt_rand() / mt_getrandmax() <= $rate;
    }
}
