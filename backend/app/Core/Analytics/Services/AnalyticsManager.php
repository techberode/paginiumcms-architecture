<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Analytics\Services;

use PaginiumCMS\Core\Analytics\Contracts\ReporterInterface;
use PaginiumCMS\Core\Analytics\Contracts\TrackerInterface;
use PaginiumCMS\Core\Analytics\Models\Visit;
use PaginiumCMS\Core\Notification\Services\IncidentNotifier;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Facade for analytics tracking and reporting (Iteration 6).
 */
final class AnalyticsManager
{
    public function __construct(
        private TrackerInterface $tracker,
        private ReporterInterface $reporter,
        private SettingsRepositoryInterface $settings,
        private ?IncidentNotifier $incidentNotifier = null
    ) {
    }

    public function trackPageView(string $uri, ?string $referer = null): void
    {
        $visit = new Visit();
        $visit->setIp($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $visit->setUserAgent($_SERVER['HTTP_USER_AGENT'] ?? '');
        $visit->setRequestUri($uri);
        $visit->setReferer($referer);
        $visit->setTimestamp(date('Y-m-d H:i:s'));

        $this->tracker->track($visit);
        $this->checkTrafficSpike();
    }

    public function reporter(): ReporterInterface
    {
        return $this->reporter;
    }

    private function checkTrafficSpike(): void
    {
        if ($this->incidentNotifier === null) {
            return;
        }

        $monitoring = $this->settings->group('monitoring');
        if (!(bool) ($monitoring['notifyTrafficSpike'] ?? false)) {
            return;
        }

        $threshold = (int) ($monitoring['trafficSpikeThreshold'] ?? 500);
        $stats = $this->tracker->getDailyStats(date('Y-m-d'));
        $visits = (int) ($stats['visits'] ?? 0);
        if ($visits > 0 && $visits % $threshold === 0) {
            $this->incidentNotifier->notify(
                'analytics.traffic_spike',
                'Traffic spike detected',
                sprintf('Hourly/daily visits reached %d (threshold %d)', $visits, $threshold),
                'info'
            );
        }
    }
}
