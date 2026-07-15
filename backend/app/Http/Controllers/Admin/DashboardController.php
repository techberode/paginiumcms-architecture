<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Analytics\Contracts\ReporterInterface;
use PaginiumCMS\Core\Analytics\Services\RealtimeTracker;
use PaginiumCMS\Core\Conflict\Contracts\ConflictLoggerInterface;
use PaginiumCMS\Core\Conflict\Models\ConflictRecord;
use PaginiumCMS\Core\Health\Services\HealthCheckManager;
use PaginiumCMS\Core\Locking\Contracts\LockManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Admin dashboard overview – locks, conflicts, health, analytics (Iteration 7).
 */
final class DashboardController
{
    public function __construct(
        private LockManagerInterface $locks,
        private ConflictLoggerInterface $conflicts,
        private HealthCheckManager $health,
        private ReporterInterface $reporter,
        private RealtimeTracker $realtime
    ) {
    }

    public function overview(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $locks = array_map(
            static fn ($lock) => $lock->jsonSerialize(),
            $this->locks->getAllLocks()
        );

        $conflicts = array_map(
            static fn (ConflictRecord $record): array => $record->jsonSerialize(),
            $this->conflicts->getRecent(10)
        );

        $healthReport = $this->health->run();

        return $this->json($response, [
            'success' => true,
            'data' => [
                'locks' => $locks,
                'locks_count' => count($locks),
                'conflicts' => $conflicts,
                'conflicts_count' => count($this->conflicts->getRecent(100)),
                'health' => $this->normalizeHealthReport($healthReport->toArray()),
                'analytics' => [
                    'overview' => $this->reporter->getOverview('today'),
                    'chart' => $this->reporter->getDailyChart(14),
                    'realtime' => $this->realtime->getSnapshot(),
                ],
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $report
     * @return array<string, mixed>
     */
    private function normalizeHealthReport(array $report): array
    {
        if (!isset($report['checks']) || !is_array($report['checks'])) {
            return $report;
        }

        $report['checks'] = array_map(static function (array $check): array {
            if (isset($check['check']) && !isset($check['name'])) {
                $check['name'] = $check['check'];
            }

            return $check;
        }, $report['checks']);

        return $report;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function json(ResponseInterface $response, array $payload, int $status = 200): ResponseInterface
    {
        $response->getBody()->write((string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
