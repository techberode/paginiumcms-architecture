<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Analytics\Services;

use PaginiumCMS\Core\Analytics\Contracts\TrackerInterface;

/**
 * Realtime analytics snapshot built on Tracker flat-file data (Iteration 7).
 */
final class RealtimeTracker
{
    private const WINDOW_SECONDS = 300;

    public function __construct(private TrackerInterface $tracker)
    {
    }

    /**
     * @return array{
     *   window_seconds: int,
     *   active_visitors: int,
     *   active_page_views: int,
     *   top_active_pages: list<array{uri: string, views: int}>
     * }
 * @return array<int|string, mixed>
 */public function getSnapshot(): array
    {
        $visits = $this->tracker->getRealtimeVisitors();
        $visitorIds = [];
        $pageCounts = [];

        foreach ($visits as $visit) {
            $visitorId = (string) ($visit['visitorId'] ?? $visit['ip'] ?? '');
            if ($visitorId !== '') {
                $visitorIds[$visitorId] = true;
            }

            $uri = (string) ($visit['requestUri'] ?? '/');
            $pageCounts[$uri] = ($pageCounts[$uri] ?? 0) + 1;
        }

        arsort($pageCounts);
        $topPages = [];
        foreach (array_slice($pageCounts, 0, 10, true) as $uri => $views) {
            $topPages[] = ['uri' => $uri, 'views' => $views];
        }

        return [
            'window_seconds' => self::WINDOW_SECONDS,
            'active_visitors' => count($visitorIds),
            'active_page_views' => count($visits),
            'top_active_pages' => $topPages,
        ];
    }
}
