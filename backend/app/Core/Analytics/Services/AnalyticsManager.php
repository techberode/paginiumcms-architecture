<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Analytics\Services;

use PaginiumCMS\Core\Analytics\Contracts\ReporterInterface;
use PaginiumCMS\Core\Analytics\Contracts\TrackerInterface;
use PaginiumCMS\Core\Analytics\Models\Visit;
use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Core\Notification\Services\IncidentNotifier;
use PaginiumCMS\Core\Security\ClientIpResolver;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Facade for analytics tracking and reporting (Iteration 6).
 */
final class AnalyticsManager
{
    public function __construct(
        private TrackerInterface $tracker,
        private ReporterInterface $reporter,
        private SettingsRepositoryInterface $settings,
        private CacheManager $cache,
        private ?IncidentNotifier $incidentNotifier = null
    ) {
    }

    public function trackPageView(string $uri, ?string $referer = null): void
    {
        $visit = $this->buildVisit($uri, $referer, 0, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', $_SERVER['HTTP_USER_AGENT'] ?? '');

        $this->tracker->track($visit);
        $this->checkTrafficSpike();
    }

    public function trackPageViewFromRequest(
        ServerRequestInterface $request,
        string $uri,
        ?string $referer,
        int $durationSeconds = 0
    ): bool {
        $trustedProxies = ClientIpResolver::trustedProxiesFromEnv();
        $ip = ClientIpResolver::resolve($request->getServerParams(), $trustedProxies);
        $userAgent = $request->getHeaderLine('User-Agent');

        $dedupeKey = 'analytics:dedupe:' . md5($ip . '|' . $uri);
        if ($this->cache->get($dedupeKey, false)) {
            return false;
        }
        $this->cache->set($dedupeKey, true, 3);

        $visit = $this->buildVisit($uri, $referer, $durationSeconds, $ip, $userAgent);
        $this->tracker->track($visit);
        $this->checkTrafficSpike();

        return true;
    }

    private function buildVisit(
        string $uri,
        ?string $referer,
        int $durationSeconds,
        string $ip,
        string $userAgent
    ): Visit {
        $visit = new Visit();
        $visit->setIp($ip);
        $visit->setUserAgent($userAgent !== '' ? $userAgent : null);
        $visit->setRequestUri($uri);
        $visit->setReferer($referer);
        $visit->setTimestamp(date('Y-m-d H:i:s'));
        if ($durationSeconds > 0) {
            $visit->setDuration($durationSeconds);
        }

        return $visit;
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
