<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Analytics\Services\RealtimeTracker;
use PaginiumCMS\Core\Analytics\Services\Reporter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use PaginiumCMS\Support\JsonHelper;

/**
 * Admin analytics API (Iteration 6).
 */
final class AnalyticsController
{
    public function __construct(
        private Reporter $reporter,
        private RealtimeTracker $realtime
    ) {
    }

    public function overview(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $period = $request->getQueryParams()['period'] ?? 'today';

        return $this->json($response, [
            'success' => true,
            'data' => [
                'overview' => $this->reporter->getOverview((string) $period),
                'top_pages' => $this->reporter->getTopPages(10, (string) $period),
                'top_referers' => $this->reporter->getTopReferers(10, (string) $period),
                'devices' => $this->reporter->getDeviceStats((string) $period),
            ],
        ]);
    }

    public function chart(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $days = (int) ($request->getQueryParams()['days'] ?? 30);

        return $this->json($response, [
            'success' => true,
            'data' => ['chart' => $this->reporter->getDailyChart(max(1, min($days, 90)))],
        ]);
    }

    public function realtime(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, [
            'success' => true,
            'data' => $this->realtime->getSnapshot(),
        ]);
    }

    /**
     * @param array<int|string, mixed> $payload
 */private function json(ResponseInterface $response, array $payload, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(JsonHelper::encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
