<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Admin\Services\AdminCountsService;
use PaginiumCMS\Core\Admin\Services\ContentStorageStatsService;
use PaginiumCMS\Core\Analytics\Contracts\ReporterInterface;
use PaginiumCMS\Core\Analytics\Services\RealtimeTracker;
use PaginiumCMS\Core\Conflict\Contracts\ConflictLoggerInterface;
use PaginiumCMS\Core\Conflict\Models\ConflictRecord;
use PaginiumCMS\Core\Health\Services\HealthCheckManager;
use PaginiumCMS\Core\Logging\Services\ApplicationLogReader;
use PaginiumCMS\Core\Locking\Contracts\LockManagerInterface;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Security\Models\User;
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
        private RealtimeTracker $realtime,
        private ApplicationLogReader $logReader,
        private AdminCountsService $counts,
        private ContentStorageStatsService $contentStorageStats,
        private JsonResponder $json
    ) {
    }

    public function overview(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $viewer = $request->getAttribute('user');
        $user = $viewer instanceof User ? $viewer : null;
        $locks = array_map(
            static fn ($lock) => $lock->jsonSerialize(),
            $this->locks->getAllLocks()
        );

        $conflicts = array_map(
            static fn (ConflictRecord $record): array => $record->jsonSerialize(),
            $this->conflicts->getRecent(10)
        );

        $healthReport = $this->health->run();
        $healthPayload = $this->normalizeHealthReport($healthReport->toArray());
        $counts = $this->counts->collect($user);

        return $this->json->success($response, [
            'locks' => $locks,
            'locks_count' => count($locks),
            'conflicts' => $conflicts,
            'conflicts_count' => count($this->conflicts->getRecent(100)),
            'health' => $healthPayload,
            'counts' => $counts,
            'storage' => [
                ...$this->extractStorageSummary($healthPayload),
                'content' => $this->contentStorageStats->summarize(
                    (int) ($counts['pages'] ?? 0),
                    (int) ($counts['articles'] ?? 0),
                    (int) ($counts['media'] ?? 0),
                    (int) ($counts['users'] ?? 0)
                ),
            ],
            'analytics' => [
                'overview' => $this->reporter->getOverview('today'),
                'chart' => $this->reporter->getDailyChart(14),
                'realtime' => $this->realtime->getSnapshot(),
            ],
            'logs' => [
                'hours' => 24,
                'by_severity' => $this->logReader->severityStats(24),
            ],
        ]);
    }

    /**
     * @param array<int|string, mixed> $report
     * @return array<int|string, mixed>
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
     * @param array<int|string, mixed> $healthReport
     * @return array{
     *     free_space: ?string,
     *     free_space_bytes: ?int,
     *     demo_synthetic?: bool,
     *     demo_quota_bytes?: int,
     *     demo_used_bytes?: int
     * }
     */
    private function extractStorageSummary(array $healthReport): array
    {
        $checks = $healthReport['checks'] ?? [];
        if (!is_array($checks)) {
            return ['free_space' => null, 'free_space_bytes' => null];
        }

        foreach ($checks as $check) {
            if (!is_array($check)) {
                continue;
            }

            $name = (string) ($check['name'] ?? $check['check'] ?? '');
            if ($name !== 'storage') {
                continue;
            }

            $data = $check['data'] ?? [];
            if (!is_array($data)) {
                break;
            }

            $bytes = $data['free_space_bytes'] ?? null;
            $summary = [
                'free_space' => isset($data['free_space']) ? (string) $data['free_space'] : null,
                'free_space_bytes' => is_numeric($bytes) ? (int) $bytes : null,
            ];

            if (($data['demo_synthetic'] ?? false) === true) {
                $summary['demo_synthetic'] = true;
                if (isset($data['demo_quota_bytes']) && is_numeric($data['demo_quota_bytes'])) {
                    $summary['demo_quota_bytes'] = (int) $data['demo_quota_bytes'];
                }
                if (isset($data['demo_used_bytes']) && is_numeric($data['demo_used_bytes'])) {
                    $summary['demo_used_bytes'] = (int) $data['demo_used_bytes'];
                }
            }

            return $summary;
        }

        return ['free_space' => null, 'free_space_bytes' => null];
    }
}
