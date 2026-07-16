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
        $visits = $this->tracker->getVisits($this->resolveDate($period), 5000);
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
        $visits = $this->tracker->getVisits($this->resolveDate($period), 5000);
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
        $visits = $this->tracker->getVisits($this->resolveDate($period), 2000);
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
        $visits = $this->tracker->getVisits($this->resolveDate($period), 2000);
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

    private function resolveDate(string $period): string
    {
        return match ($period) {
            'yesterday' => date('Y-m-d', strtotime('-1 day')),
            default => date('Y-m-d'),
        };
    }
}
