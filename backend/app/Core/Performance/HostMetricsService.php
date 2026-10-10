<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Performance;

/**
 * Admin-facing host metrics view + freshness checks (It.82d).
 */
final class HostMetricsService
{
    public function __construct(
        private HostMetricsSettings $settings,
        private HostMetricsStore $store
    ) {
    }

    /**
     * @return array{
     *     enabled: bool,
     *     status: 'disabled'|'missing'|'stale'|'ok',
     *     max_age_seconds: int,
     *     collected_at: string|null,
     *     age_seconds: int|null,
     *     snapshot: array<string, mixed>|null
     * }
     */
    public function publicView(): array
    {
        if (!$this->settings->enabled()) {
            return [
                'enabled' => false,
                'status' => 'disabled',
                'max_age_seconds' => $this->settings->maxAgeSeconds(),
                'collected_at' => null,
                'age_seconds' => null,
                'snapshot' => null,
            ];
        }

        $snapshot = $this->store->latest();
        if ($snapshot === null || $snapshot === []) {
            return [
                'enabled' => true,
                'status' => 'missing',
                'max_age_seconds' => $this->settings->maxAgeSeconds(),
                'collected_at' => null,
                'age_seconds' => null,
                'snapshot' => null,
            ];
        }

        $collectedAt = (string) ($snapshot['collected_at'] ?? '');
        $ts = $collectedAt !== '' ? strtotime($collectedAt) : false;
        if ($ts === false) {
            return [
                'enabled' => true,
                'status' => 'missing',
                'max_age_seconds' => $this->settings->maxAgeSeconds(),
                'collected_at' => null,
                'age_seconds' => null,
                'snapshot' => null,
            ];
        }

        $age = max(0, time() - $ts);
        $status = $age <= $this->settings->maxAgeSeconds() ? 'ok' : 'stale';

        return [
            'enabled' => true,
            'status' => $status,
            'max_age_seconds' => $this->settings->maxAgeSeconds(),
            'collected_at' => gmdate('c', $ts),
            'age_seconds' => $age,
            'snapshot' => $snapshot,
        ];
    }

    public function snapshotFreshForReport(): bool
    {
        $view = $this->publicView();

        return $view['enabled'] === true && $view['status'] === 'ok' && is_array($view['snapshot']);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function snapshotForReport(): ?array
    {
        if (!$this->snapshotFreshForReport()) {
            return null;
        }

        $view = $this->publicView();

        return is_array($view['snapshot']) ? $view['snapshot'] : null;
    }
}
