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
     * @return array<int|string, mixed>
     */
    public function getOverview(string $period = 'today'): array
    {
        $date = $this->resolveDate($period);
        $stats = $this->tracker->getDailyStats($date);
        $realtime = $this->tracker->getRealtimeVisitors();

        return [
            'period' => $period,
            'date' => $date,
            'visits' => (int) ($stats['visits'] ?? 0),
            'page_views' => (int) ($stats['page_views'] ?? 0),
            'unique_visitors' => (int) ($stats['unique_visitors'] ?? 0),
            'bounce_rate' => (float) ($stats['bounce_rate'] ?? 0),
            'realtime_visitors' => count($realtime),
        ];
    }

    /**
     * @return array<int|string, mixed>
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
     * @return array<int|string, mixed>
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
     * @return array<int|string, mixed>
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
     * @return array<int|string, mixed>
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
     * @return array<int|string, mixed>
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
                $date = date('Y-m-d', strtotime('-' . $i . ' days'));
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
        if ($period === 'week') {
            $all = [];
            for ($i = 0; $i < 7; $i++) {
                $date = date('Y-m-d', strtotime('-' . $i . ' days'));
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

    private function resolveDate(string $period): string
    {
        return match ($period) {
            'yesterday' => date('Y-m-d', strtotime('-1 day')),
            'week' => date('Y-m-d'),
            default => date('Y-m-d'),
        };
    }
}
