<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Analytics\Services\RealtimeTracker;
use PaginiumCMS\Core\Analytics\Services\Reporter;
use PaginiumCMS\Http\Support\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Admin analytics API (Iteration 6).
 */
final class AnalyticsController
{
    public function __construct(
        private Reporter $reporter,
        private RealtimeTracker $realtime,
        private JsonResponder $json
    ) {
    }

    public function overview(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $period = $request->getQueryParams()['period'] ?? 'today';

        return $this->json->success($response, [
            'overview' => $this->reporter->getOverview((string) $period),
            'top_pages' => $this->reporter->getTopPages(10, (string) $period),
            'top_articles' => $this->reporter->getTopArticles(10, (string) $period),
            'top_referers' => $this->reporter->getTopReferers(10, (string) $period),
            'devices' => $this->reporter->getDeviceStats((string) $period),
            'browsers' => $this->reporter->getBrowserStats((string) $period),
            'geo' => $this->reporter->getGeoStats((string) $period),
        ]);
    }

    public function chart(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $days = (int) ($request->getQueryParams()['days'] ?? 30);

        return $this->json->success($response, [
            'chart' => $this->reporter->getDailyChart(max(1, min($days, 90))),
        ]);
    }

    public function realtime(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, $this->realtime->getSnapshot());
    }
}
