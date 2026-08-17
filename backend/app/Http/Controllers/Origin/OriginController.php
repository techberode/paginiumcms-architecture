<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Origin;

use PaginiumCMS\Core\Admin\Services\AdminCountsService;
use PaginiumCMS\Core\Health\Services\HealthCheckManager;
use PaginiumCMS\Support\LogSanitizer;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Origin\Services\FeatureProbeRegistry;
use PaginiumCMS\Modules\Origin\Services\ProjectCatalogMergeService;
use PaginiumCMS\Modules\Origin\Services\OriginPanelMode;
use PaginiumCMS\Modules\Security\Models\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Maintainer-only Origin Panel API (It.82).
 */
final class OriginController
{
    public function __construct(
        private FeatureProbeRegistry $probes,
        private ProjectCatalogMergeService $catalog,
        private HealthCheckManager $health,
        private AdminCountsService $counts,
        private JsonResponder $json,
    ) {
    }

    public function overview(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $inactive = $this->inactiveResponse($request, $response);
        if ($inactive !== null) {
            return $inactive;
        }

        $viewer = $request->getAttribute('user');
        $user = $viewer instanceof User ? $viewer : null;
        $healthReport = $this->health->run();
        $probeRows = $this->probes->runAll();
        $sanitizedProbes = array_map(
            static fn (array $row): array => [
                ...$row,
                'message' => LogSanitizer::value($row['message']),
            ],
            $probeRows
        );

        return $this->json->success($response, [
            'health' => $healthReport->toArray(),
            'counts' => $this->counts->collect($user),
            'probes' => $sanitizedProbes,
            'summary' => $this->summarizeProbes($sanitizedProbes),
            'catalog' => $this->catalogPayload($sanitizedProbes),
        ]);
    }

    public function probes(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $inactive = $this->inactiveResponse($request, $response);
        if ($inactive !== null) {
            return $inactive;
        }

        $probeRows = array_map(
            static fn (array $row): array => [
                ...$row,
                'message' => LogSanitizer::value($row['message']),
            ],
            $this->probes->runAll()
        );

        return $this->json->success($response, [
            'probes' => $probeRows,
            'summary' => $this->summarizeProbes($probeRows),
            'catalog' => $this->catalogPayload($probeRows),
        ]);
    }

    public function catalog(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $inactive = $this->inactiveResponse($request, $response);
        if ($inactive !== null) {
            return $inactive;
        }

        $probeRows = array_map(
            static fn (array $row): array => [
                ...$row,
                'message' => LogSanitizer::value($row['message']),
            ],
            $this->probes->runAll()
        );

        return $this->json->success($response, $this->catalogPayload($probeRows));
    }

    /**
     * @param list<array{id: string, status: string, message: string, since: string|null, group: string, labelKey: string}> $probes
     *
     * @return array<string, mixed>
     */
    private function catalogPayload(array $probes): array
    {
        $merged = $this->catalog->merge($probes);

        return [
            ...$merged,
            'timeline' => array_map(
                static fn (array $entry): array => [
                    ...$entry,
                    'summaryKey' => (string) ($entry['summaryKey'] ?? ''),
                ],
                $merged['timeline']
            ),
        ];
    }

    private function inactiveResponse(ServerRequestInterface $request, ResponseInterface $response): ?ResponseInterface
    {
        $host = $request->getUri()->getHost();
        if (OriginPanelMode::isActive($host)) {
            return null;
        }

        return $this->json->error($response, 'Not found', 404);
    }

    /**
     * @param list<array{status: string}> $probes
     *
     * @return array{implemented: int, partial: int, missing: int, unknown: int, total: int}
     */
    private function summarizeProbes(array $probes): array
    {
        $summary = [
            'implemented' => 0,
            'partial' => 0,
            'missing' => 0,
            'unknown' => 0,
            'total' => count($probes),
        ];

        foreach ($probes as $probe) {
            $status = $probe['status'];
            if (!array_key_exists($status, $summary)) {
                $summary['unknown']++;
                continue;
            }
            $summary[$status]++;
        }

        return $summary;
    }
}
