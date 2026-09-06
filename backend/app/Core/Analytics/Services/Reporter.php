<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Analytics\Services;

use PaginiumCMS\Core\Analytics\Contracts\ReporterInterface;
use PaginiumCMS\Core\Analytics\Contracts\TrackerInterface;
use PaginiumCMS\Core\Security\BotClassification;
use PaginiumCMS\Core\Security\UserAgentBotClassifier;

/**
 * Analytics reports built on top of Tracker flat-file data (Iteration 6).
 */
final class Reporter implements ReporterInterface
{
    private RefererAnalyzer $refererAnalyzer;

    public function __construct(private TrackerInterface $tracker)
    {
        $this->refererAnalyzer = new RefererAnalyzer();
    }

    /**
     * @return array<string, mixed>
     */
    public function getOverview(string $period = 'today'): array
    {
        $days = $this->resolvePeriodDays($period);
        if ($days > 1) {
            $overview = $this->getAggregatedOverviewForDays($days, $period);
        } else {
            $date = $this->resolveDate($period);
            $stats = $this->tracker->getDailyStats($date);
            $realtime = $this->tracker->getRealtimeVisitors();
            $visits = $this->tracker->getVisits($date, 10000);
            $avgDuration = $this->averageDuration($visits);

            $overview = [
                'period' => $period,
                'date' => $date,
                'days' => 1,
                'visits' => (int) ($stats['visits'] ?? 0),
                'page_views' => (int) ($stats['page_views'] ?? 0),
                'unique_visitors' => $this->countUniqueVisitors($visits),
                'bounce_rate' => $this->calculateBounceRate($visits),
                'avg_duration_seconds' => $avgDuration,
                'realtime_visitors' => count($realtime),
            ];
        }

        $overview['trends'] = $this->buildTrends($days);

        return $overview;
    }

    /**
     * @return list<array{uri: string, views: int}>
     */
    public function getTopPages(int $limit = 10, string $period = 'today'): array
    {
        $visits = $this->collectVisitsForPeriod($period, 5000);
        $counts = [];
        foreach ($visits as $visit) {
            $uri = (string) ($visit['requestUri'] ?? '/');
            $counts[$uri] = ($counts[$uri] ?? 0) + 1;
        }
        arsort($counts);
        $top = [];
        foreach (array_slice($counts, 0, $limit, true) as $uri => $count) {
            $top[] = ['uri' => $uri, 'views' => $count];
        }

        return $top;
    }

    /**
     * @return list<array{referer: string, source: string, domain: string, type: string, visits: int}>
     */
    public function getTopReferers(int $limit = 10, string $period = 'today'): array
    {
        $visits = $this->collectVisitsForPeriod($period, 5000);
        /** @var array<string, array{referer: string, source: string, domain: string, type: string, visits: int}> $counts */
        $counts = [];
        foreach ($visits as $visit) {
            $rawReferer = (string) ($visit['referer'] ?? 'direct');
            $analysis = $this->refererAnalyzer->analyze($rawReferer);
            $key = $this->refererAnalyzer->groupingKey($rawReferer);
            if (!isset($counts[$key])) {
                $counts[$key] = [
                    'referer' => $analysis['referer'],
                    'source' => $analysis['source'],
                    'domain' => $analysis['domain'],
                    'type' => $analysis['type'],
                    'visits' => 0,
                ];
            }
            $counts[$key]['visits']++;
        }

        uasort($counts, static fn (array $a, array $b): int => $b['visits'] <=> $a['visits']);

        return array_values(array_slice($counts, 0, $limit));
    }

    /**
     * @return array<string, int>
     */
    public function getDeviceStats(string $period = 'today'): array
    {
        $visits = $this->collectVisitsForPeriod($period, 2000);
        $counts = ['desktop' => 0, 'mobile' => 0, 'tablet' => 0, 'unknown' => 0];
        foreach ($visits as $visit) {
            $type = strtolower((string) ($visit['deviceType'] ?? 'unknown'));
            if (!isset($counts[$type])) {
                $type = 'unknown';
            }
            $counts[$type]++;
        }

        return $counts;
    }

    /**
     * @return list<array{browser: string, visits: int}>
     */
    public function getBrowserStats(string $period = 'today'): array
    {
        $visits = $this->collectVisitsForPeriod($period, 2000);
        $counts = [];
        foreach ($visits as $visit) {
            $browser = trim((string) ($visit['browser'] ?? 'Unknown'));
            if ($browser === '') {
                $browser = 'Unknown';
            }
            $counts[$browser] = ($counts[$browser] ?? 0) + 1;
        }
        arsort($counts);

        $top = [];
        foreach ($counts as $browser => $count) {
            $top[] = ['browser' => $browser, 'visits' => $count];
        }

        return $top;
    }

    /**
     * @return list<array{platform: string, visits: int}>
     */
    public function getPlatformStats(string $period = 'today'): array
    {
        $visits = $this->collectVisitsForPeriod($period, 5000);
        $counts = [];

        foreach ($visits as $visit) {
            $platform = trim((string) ($visit['platformLabel'] ?? ''));
            if ($platform === '') {
                $ua = isset($visit['userAgent']) && is_string($visit['userAgent']) ? $visit['userAgent'] : null;
                $platform = (new DeviceDetector($ua))->getPlatformLabel();
            }
            if ($platform === '') {
                $platform = 'Unknown';
            }
            $counts[$platform] = ($counts[$platform] ?? 0) + 1;
        }

        arsort($counts);

        $top = [];
        foreach ($counts as $platform => $count) {
            $top[] = ['platform' => $platform, 'visits' => $count];
        }

        return $top;
    }

    /**
     * @return list<array{country: string, countryCode: string|null, city: string|null, visits: int, sample_ips: list<string>}>
     */
    public function getGeoStats(string $period = 'today'): array
    {
        $visits = $this->collectVisitsForPeriod($period, 2000);
        /** @var array<string, array{country: string, countryCode: string|null, cityCounts: array<string, int>, visits: int, sample_ips: list<string>}> $stats */
        $stats = [];

        foreach ($visits as $visit) {
            $country = (string) ($visit['country'] ?? 'Unknown');
            $countryCode = isset($visit['countryCode']) && is_string($visit['countryCode']) && $visit['countryCode'] !== ''
                ? strtoupper($visit['countryCode'])
                : null;
            $city = isset($visit['city']) && is_string($visit['city']) && $visit['city'] !== ''
                ? $visit['city']
                : null;
            $key = ($countryCode ?? 'xx') . '|' . $country;

            if (!isset($stats[$key])) {
                $stats[$key] = [
                    'country' => $country,
                    'countryCode' => $countryCode,
                    'cityCounts' => [],
                    'visits' => 0,
                    'sample_ips' => [],
                ];
            }

            $stats[$key]['visits']++;
            if ($city !== null) {
                $stats[$key]['cityCounts'][$city] = ($stats[$key]['cityCounts'][$city] ?? 0) + 1;
            }

            $maskedIp = AnalyticsIpMasker::mask(isset($visit['ip']) ? (string) $visit['ip'] : null);
            if ($maskedIp !== 'unknown' && !in_array($maskedIp, $stats[$key]['sample_ips'], true) && count($stats[$key]['sample_ips']) < 3) {
                $stats[$key]['sample_ips'][] = $maskedIp;
            }
        }

        uasort($stats, static fn (array $a, array $b): int => $b['visits'] <=> $a['visits']);

        $rows = [];
        foreach ($stats as $row) {
            arsort($row['cityCounts']);
            $topCity = array_key_first($row['cityCounts']);
            $rows[] = [
                'country' => $row['country'],
                'countryCode' => $row['countryCode'],
                'city' => is_string($topCity) ? $topCity : null,
                'visits' => $row['visits'],
                'sample_ips' => $row['sample_ips'],
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{country: string, countryCode: string|null, city: string|null, ip_masked: string, requestUri: string, timestamp: string}>
     */
    public function getRecentGeoVisits(int $limit = 20, string $period = 'today'): array
    {
        $visits = $this->collectVisitsForPeriod($period, 2000);
        usort(
            $visits,
            static fn (array $a, array $b): int => strcmp((string) ($b['timestamp'] ?? ''), (string) ($a['timestamp'] ?? ''))
        );

        $rows = [];
        foreach (array_slice($visits, 0, $limit) as $visit) {
            $rows[] = [
                'country' => (string) ($visit['country'] ?? 'Unknown'),
                'countryCode' => isset($visit['countryCode']) && is_string($visit['countryCode']) && $visit['countryCode'] !== ''
                    ? strtoupper($visit['countryCode'])
                    : null,
                'city' => isset($visit['city']) && is_string($visit['city']) ? $visit['city'] : null,
                'ip_masked' => AnalyticsIpMasker::mask(isset($visit['ip']) ? (string) $visit['ip'] : null),
                'requestUri' => (string) ($visit['requestUri'] ?? '/'),
                'timestamp' => (string) ($visit['timestamp'] ?? ''),
            ];
        }

        return $rows;
    }

    /**
     * @return array{human: int, bot: int, bot_share: float}
     */
    public function getBotSummary(string $period = 'today'): array
    {
        $visits = $this->collectVisitsForPeriod($period, 5000);
        $human = 0;
        $bot = 0;

        foreach ($visits as $visit) {
            if ($this->botMetaForVisit($visit)->isBot()) {
                ++$bot;
            } else {
                ++$human;
            }
        }

        $total = $human + $bot;

        return [
            'human' => $human,
            'bot' => $bot,
            'bot_share' => $total > 0 ? round(($bot / $total) * 100, 1) : 0.0,
        ];
    }

    /**
     * @return list<array{botName: string, botKind: string, visits: int}>
     */
    public function getTopBots(int $limit = 12, string $period = 'today'): array
    {
        $visits = $this->collectVisitsForPeriod($period, 5000);
        /** @var array<string, array{botName: string, botKind: string, visits: int}> $counts */
        $counts = [];

        foreach ($visits as $visit) {
            $meta = $this->botMetaForVisit($visit);
            if (!$meta->isBot()) {
                continue;
            }

            $name = $meta->botName ?? 'Unknown bot';
            $kind = $meta->botKind ?? 'generic';
            $key = $kind . '|' . $name;
            if (!isset($counts[$key])) {
                $counts[$key] = [
                    'botName' => $name,
                    'botKind' => $kind,
                    'visits' => 0,
                ];
            }
            $counts[$key]['visits']++;
        }

        uasort($counts, static fn (array $a, array $b): int => $b['visits'] <=> $a['visits']);

        return array_values(array_slice($counts, 0, $limit));
    }

    /**
     * @return list<array{botName: string, botKind: string, requestUri: string, ip: string, ip_masked: string, timestamp: string, blockRecommended: bool}>
     */
    public function getRecentBotVisits(int $limit = 15, string $period = 'today'): array
    {
        $visits = $this->collectVisitsForPeriod($period, 2000);
        usort(
            $visits,
            static fn (array $a, array $b): int => strcmp((string) ($b['timestamp'] ?? ''), (string) ($a['timestamp'] ?? ''))
        );

        $rows = [];
        foreach ($visits as $visit) {
            $meta = $this->botMetaForVisit($visit);
            if (!$meta->isBot()) {
                continue;
            }

            $rows[] = [
                'botName' => $meta->botName ?? 'Unknown bot',
                'botKind' => $meta->botKind ?? 'generic',
                'requestUri' => (string) ($visit['requestUri'] ?? '/'),
                'ip' => (string) ($visit['ip'] ?? ''),
                'ip_masked' => AnalyticsIpMasker::mask(isset($visit['ip']) ? (string) $visit['ip'] : null),
                'timestamp' => (string) ($visit['timestamp'] ?? ''),
                'blockRecommended' => $meta->shouldBlock,
            ];

            if (count($rows) >= $limit) {
                break;
            }
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getDailyChart(int $days = 30): array
    {
        $chart = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $timestamp = strtotime('-' . $i . ' days');
            $date = date('Y-m-d', $timestamp !== false ? $timestamp : time());
            $stats = $this->tracker->getDailyStats($date);
            $chart[] = [
                'date' => $date,
                'visits' => (int) ($stats['visits'] ?? 0),
                'page_views' => (int) ($stats['page_views'] ?? 0),
            ];
        }

        return $chart;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAggregatedOverview(string $interval = 'day'): array
    {
        if ($interval === 'week') {
            $visits = 0;
            $pageViews = 0;
            $unique = 0;
            for ($i = 0; $i < 7; $i++) {
                $date = $this->dateDaysAgo($i);
                $stats = $this->tracker->getDailyStats($date);
                $visits += (int) ($stats['visits'] ?? 0);
                $pageViews += (int) ($stats['page_views'] ?? 0);
                $unique += (int) ($stats['unique_visitors'] ?? 0);
            }

            return [
                'period' => 'week',
                'date' => date('Y-m-d'),
                'visits' => $visits,
                'page_views' => $pageViews,
                'unique_visitors' => $unique,
                'bounce_rate' => 0.0,
                'realtime_visitors' => count($this->tracker->getRealtimeVisitors()),
            ];
        }

        if ($interval === 'hour') {
            $overview = $this->getOverview('today');
            $overview['period'] = 'hour';

            return $overview;
        }

        return $this->getOverview('today');
    }

    /**
     * @return list<array{ip: string, visits: int, top_uri: string}>
     */
    public function getTopIpStats(int $limit = 15, string $period = 'today'): array
    {
        $visits = $this->collectVisitsForPeriod($period, 5000);
        /** @var array<string, array{visits: int, pages: array<string, int>}> $stats */
        $stats = [];

        foreach ($visits as $visit) {
            $ip = (string) ($visit['ip'] ?? 'unknown');
            if ($ip === '') {
                $ip = 'unknown';
            }
            if (!isset($stats[$ip])) {
                $stats[$ip] = ['visits' => 0, 'pages' => []];
            }
            ++$stats[$ip]['visits'];
            $uri = (string) ($visit['requestUri'] ?? '/');
            $stats[$ip]['pages'][$uri] = ($stats[$ip]['pages'][$uri] ?? 0) + 1;
        }

        uasort($stats, static fn (array $a, array $b): int => $b['visits'] <=> $a['visits']);

        $top = [];
        foreach (array_slice($stats, 0, $limit, true) as $ip => $row) {
            arsort($row['pages']);
            $topUri = (string) array_key_first($row['pages']);
            $top[] = [
                'ip' => $ip,
                'visits' => $row['visits'],
                'top_uri' => $topUri !== '' ? $topUri : '/',
            ];
        }

        return $top;
    }

    /**
     * @return list<array{uri: string, views: int, title: string}>
     */
    public function getTopArticles(int $limit = 10, string $period = 'today'): array
    {
        $visits = $this->collectVisitsForPeriod($period, 5000);
        $counts = [];

        foreach ($visits as $visit) {
            $uri = (string) ($visit['requestUri'] ?? '/');
            if (!$this->looksLikeArticleUri($uri)) {
                continue;
            }
            $counts[$uri] = ($counts[$uri] ?? 0) + 1;
        }

        arsort($counts);
        $top = [];
        foreach (array_slice($counts, 0, $limit, true) as $uri => $count) {
            $top[] = [
                'uri' => $uri,
                'views' => $count,
                'title' => $this->humanizeArticleTitle($uri),
            ];
        }

        return $top;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function collectVisitsForPeriod(string $period, int $limitPerDay = 5000): array
    {
        $days = $this->resolvePeriodDays($period);
        if ($days > 1) {
            $all = [];
            for ($i = 0; $i < $days; $i++) {
                $date = $this->dateDaysAgo($i);
                $all = [...$all, ...$this->tracker->getVisits($date, $limitPerDay)];
            }

            return $all;
        }

        if ($period === 'week') {
            $all = [];
            for ($i = 0; $i < 7; $i++) {
                $date = $this->dateDaysAgo($i);
                $all = [...$all, ...$this->tracker->getVisits($date, $limitPerDay)];
            }

            return $all;
        }

        return $this->tracker->getVisits($this->resolveDate($period), $limitPerDay);
    }

    private function looksLikeArticleUri(string $uri): bool
    {
        if ($uri === '/' || str_starts_with($uri, '/api') || str_starts_with($uri, '/storage')) {
            return false;
        }

        return (bool) preg_match('#/(blog|article|articles|clanky|posts|novinky)(/|$)#i', $uri);
    }

    private function humanizeArticleTitle(string $uri): string
    {
        $slug = trim((string) preg_replace('#^.*/#', '', $uri), '/');
        if ($slug === '') {
            return $uri;
        }

        return ucwords(str_replace(['-', '_'], ' ', $slug));
    }

    private function dateDaysAgo(int $offset): string
    {
        $timestamp = strtotime(sprintf('-%d days', $offset));

        return date('Y-m-d', $timestamp ?: time());
    }

    private function resolveDate(string $period): string
    {
        return match ($period) {
            'yesterday' => $this->dateDaysAgo(1),
            default => date('Y-m-d'),
        };
    }

    private function resolvePeriodDays(string $period): int
    {
        return match ($period) {
            'week', '7d', '7' => 7,
            '14d', '14' => 14,
            '30d', '30' => 30,
            default => preg_match('/^(\d+)d?$/', $period, $matches) === 1
                ? max(1, min(90, (int) $matches[1]))
                : 1,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function getAggregatedOverviewForDays(int $days, string $periodLabel): array
    {
        $visits = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $this->dateDaysAgo($i);
            $visits = [...$visits, ...$this->tracker->getVisits($date, 10000)];
        }

        return [
            'period' => $periodLabel,
            'date' => date('Y-m-d'),
            'days' => $days,
            'visits' => count($visits),
            'page_views' => count($visits),
            'unique_visitors' => $this->countUniqueVisitors($visits),
            'bounce_rate' => $this->calculateBounceRate($visits),
            'avg_duration_seconds' => $this->averageDuration($visits),
            'realtime_visitors' => count($this->tracker->getRealtimeVisitors()),
        ];
    }

    /**
     * @param list<array<string, mixed>> $visits
     */
    private function countUniqueVisitors(array $visits): int
    {
        $ids = [];
        foreach ($visits as $visit) {
            $id = (string) ($visit['visitorId'] ?? '');
            if ($id !== '') {
                $ids[$id] = true;
            }
        }

        return count($ids);
    }

    /**
     * @param list<array<string, mixed>> $visits
     */
    private function averageDuration(array $visits): int
    {
        $total = 0;
        $count = 0;
        foreach ($visits as $visit) {
            $duration = (int) ($visit['duration'] ?? 0);
            if ($duration <= 0) {
                continue;
            }
            $total += $duration;
            ++$count;
        }

        return $count > 0 ? (int) round($total / $count) : 0;
    }

    /**
     * @param list<array<string, mixed>> $visits
     */
    private function calculateBounceRate(array $visits): float
    {
        /** @var array<string, int> $counts */
        $counts = [];
        foreach ($visits as $visit) {
            $visitorId = (string) ($visit['visitorId'] ?? '');
            if ($visitorId === '') {
                continue;
            }
            $counts[$visitorId] = ($counts[$visitorId] ?? 0) + 1;
        }

        if ($counts === []) {
            return 0.0;
        }

        $bounces = 0;
        foreach ($counts as $count) {
            if ($count === 1) {
                ++$bounces;
            }
        }

        return round(($bounces / count($counts)) * 100, 1);
    }

    /**
     * @param array<string, mixed> $visit
     */
    private function botMetaForVisit(array $visit): BotClassification
    {
        $storedType = isset($visit['visitorType']) && is_string($visit['visitorType'])
            ? $visit['visitorType']
            : null;

        if ($storedType === 'human' || $storedType === 'bot') {
            $userAgent = isset($visit['userAgent']) && is_string($visit['userAgent']) ? $visit['userAgent'] : null;
            $classified = UserAgentBotClassifier::classify($userAgent);

            return new BotClassification(
                $storedType,
                isset($visit['botName']) && is_string($visit['botName']) ? $visit['botName'] : $classified->botName,
                isset($visit['botKind']) && is_string($visit['botKind']) ? $visit['botKind'] : $classified->botKind,
                $classified->shouldBlock
            );
        }

        return UserAgentBotClassifier::classify(
            isset($visit['userAgent']) && is_string($visit['userAgent']) ? $visit['userAgent'] : null
        );
    }

    /**
     * @return array<string, array{delta: float, percent: float, direction: string}>
     */
    private function buildTrends(int $days): array
    {
        $currentVisits = $this->collectVisitsForWindow($days, 0);
        $previousVisits = $this->collectVisitsForWindow($days, $days);

        $current = [
            'page_views' => count($currentVisits),
            'unique_visitors' => $this->countUniqueVisitors($currentVisits),
            'bounce_rate' => $this->calculateBounceRate($currentVisits),
            'avg_duration_seconds' => $this->averageDuration($currentVisits),
        ];
        $previous = [
            'page_views' => count($previousVisits),
            'unique_visitors' => $this->countUniqueVisitors($previousVisits),
            'bounce_rate' => $this->calculateBounceRate($previousVisits),
            'avg_duration_seconds' => $this->averageDuration($previousVisits),
        ];

        return [
            'page_views' => $this->trendDelta((float) $current['page_views'], (float) $previous['page_views']),
            'unique_visitors' => $this->trendDelta((float) $current['unique_visitors'], (float) $previous['unique_visitors']),
            'bounce_rate' => $this->trendDelta($current['bounce_rate'], $previous['bounce_rate']),
            'avg_duration_seconds' => $this->trendDelta(
                (float) $current['avg_duration_seconds'],
                (float) $previous['avg_duration_seconds']
            ),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function collectVisitsForWindow(int $days, int $offsetDays): array
    {
        $all = [];
        for ($i = $offsetDays; $i < $offsetDays + $days; $i++) {
            $all = [...$all, ...$this->tracker->getVisits($this->dateDaysAgo($i), 10000)];
        }

        return $all;
    }

    /**
     * @return array{delta: float, percent: float, direction: string}
     */
    private function trendDelta(float $current, float $previous): array
    {
        $delta = $current - $previous;
        if ($previous <= 0.0) {
            return [
                'delta' => $delta,
                'percent' => $current > 0.0 ? 100.0 : 0.0,
                'direction' => $delta >= 0.0 ? 'up' : 'down',
            ];
        }

        return [
            'delta' => round($delta, 1),
            'percent' => round(($delta / $previous) * 100, 1),
            'direction' => $delta >= 0.0 ? 'up' : 'down',
        ];
    }
}
