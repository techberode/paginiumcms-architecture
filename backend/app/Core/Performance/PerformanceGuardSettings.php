<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Performance;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Typed view of engine Performance Guard settings (Iteration 71).
 */
final class PerformanceGuardSettings
{
    public const REMEDIATION_OFF = 'off';
    public const REMEDIATION_SUGGEST = 'suggest';
    public const REMEDIATION_AUTOMATIC = 'automatic';

    public function __construct(
        private SettingsRepositoryInterface $settings
    ) {
    }

    public function enabled(): bool
    {
        return (bool) ($this->engine()['performanceGuardEnabled'] ?? false);
    }

    public function sampleRate(): float
    {
        $rate = (float) ($this->engine()['performanceGuardSampleRate'] ?? 1.0);

        return max(0.0, min(1.0, $rate));
    }

    public function latencyMsWarning(): int
    {
        return max(1, (int) ($this->engine()['performanceGuardLatencyMsWarning'] ?? 200));
    }

    public function latencyMsCritical(): int
    {
        return max(
            $this->latencyMsWarning(),
            (int) ($this->engine()['performanceGuardLatencyMsCritical'] ?? 500)
        );
    }

    public function breachCount(): int
    {
        return max(1, (int) ($this->engine()['performanceGuardBreachCount'] ?? 3));
    }

    public function windowMinutes(): int
    {
        return max(1, (int) ($this->engine()['performanceGuardWindowMinutes'] ?? 10));
    }

    public function remediationMode(): string
    {
        $mode = (string) ($this->engine()['performanceGuardRemediationMode'] ?? self::REMEDIATION_SUGGEST);

        return in_array($mode, [self::REMEDIATION_OFF, self::REMEDIATION_SUGGEST, self::REMEDIATION_AUTOMATIC], true)
            ? $mode
            : self::REMEDIATION_SUGGEST;
    }

    /**
     * @return array<string, mixed>
     */
    public function publicSummary(): array
    {
        return [
            'enabled' => $this->enabled(),
            'sample_rate' => $this->sampleRate(),
            'latency_ms_warning' => $this->latencyMsWarning(),
            'latency_ms_critical' => $this->latencyMsCritical(),
            'breach_count' => $this->breachCount(),
            'window_minutes' => $this->windowMinutes(),
            'remediation_mode' => $this->remediationMode(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function engine(): array
    {
        return $this->settings->group('engine');
    }
}
