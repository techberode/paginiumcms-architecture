<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Analytics\Services\Reporter;
use PaginiumCMS\Core\Analytics\Services\RealtimeTracker;
use PaginiumCMS\Core\Security\Firewall\FirewallService;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Support\RequestJsonBody;
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
        private FirewallService $firewall,
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
            'platforms' => $this->reporter->getPlatformStats((string) $period),
            'browsers' => $this->reporter->getBrowserStats((string) $period),
            'geo' => $this->reporter->getGeoStats((string) $period),
            'geo_visits' => $this->reporter->getRecentGeoVisits(15, (string) $period),
            'bot_summary' => $this->reporter->getBotSummary((string) $period),
            'top_bots' => $this->reporter->getTopBots(12, (string) $period),
            'bot_visits' => $this->reporter->getRecentBotVisits(15, (string) $period),
        ]);
    }

    public function banBot(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        /** @var array<string, mixed> $data */
        $data = RequestJsonBody::decode($request) ?? [];
        $ip = filter_var((string) ($data['ip'] ?? ''), FILTER_VALIDATE_IP);
        if ($ip === false) {
            return $this->json->error($response, 'Invalid IP address', 422);
        }

        $botName = trim((string) ($data['bot_name'] ?? 'Unknown bot'));
        if ($botName === '') {
            $botName = 'Unknown bot';
        }

        $reason = sprintf('analytics:bot:%s', mb_substr($botName, 0, 120));
        $ban = $this->firewall->banFromAnalytics($ip, $reason, $botName);

        return $this->json->success($response, [
            'ban' => $ban,
        ], 200, 'Bot IP added to temporary jail');
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
