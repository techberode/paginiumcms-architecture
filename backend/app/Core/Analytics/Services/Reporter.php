<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Analytics\Services;

use PaginiumCMS\Core\Analytics\Contracts\ReporterInterface;
use PaginiumCMS\Core\Analytics\Contracts\TrackerInterface;

/**
 * Analytics reports built on top of Tracker flat-file data (Iteration 6).
 */
final class Reporter implements ReporterInterface
{
    public function __construct(private TrackerInterface $tracker)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function getOverview(string $period = 'today'): array
    {
        $days = $this->resolvePeriodDays($period);
        if ($days > 1) {
            return $this->getAggregatedOverviewForDays($days, $period);
        }

        $date = $this->resolveDate($period);
        $stats = $this->tracker->getDailyStats($date);
        $realtime = $this->tracker->getRealtimeVisitors();
        $visits = $this->tracker->getVisits($date, 10000);
        $avgDuration = $this->averageDuration($visits);

        return [
            'period' => $period,
            'date' => $date,
            'days' => 1,
            'visits' => (int) ($stats['visits'] ?? 0),
            'page_views' => (int) ($stats['page_views'] ?? 0),
            'unique_visitors' => $this->countUniqueVisitors($visits),
            'bounce_rate' => (float) ($stats['bounce_rate'] ?? 0),
            'avg_duration_seconds' => $avgDuration,
            'realtime_visitors' => count($realtime),
        ];
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
     * @return list<array{referer: string, visits: int}>
     */
    public function getTopReferers(int $limit = 10, string $period = 'today'): array
    {
        $visits = $this->collectVisitsForPeriod($period, 5000);
        $counts = [];
        foreach ($visits as $visit) {
            $ref = (string) ($visit['referer'] ?? 'direct');
            if ($ref === '') {
                $ref = 'direct';
            }
            $counts[$ref] = ($counts[$ref] ?? 0) + 1;
        }
        arsort($counts);
        $top = [];
        foreach (array_slice($counts, 0, $limit, true) as $referer => $count) {
            $top[] = ['referer' => $referer, 'visits' => $count];
        }

        return $top;
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
     * @return list<array{country: string, visits: int}>
     */
    public function getGeoStats(string $period = 'today'): array
    {
        $visits = $this->collectVisitsForPeriod($period, 2000);
        $counts = [];
        foreach ($visits as $visit) {
            $country = (string) ($visit['country'] ?? 'Unknown');
            $counts[$country] = ($counts[$country] ?? 0) + 1;
        }
        arsort($counts);

        return array_map(
            static fn (string $country, int $visits): array => ['country' => $country, 'visits' => $visits],
            array_keys($counts),
            array_values($counts)
        );
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
            'bounce_rate' => 0.0,
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
}
