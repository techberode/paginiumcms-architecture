<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Services;

/**
 * Merges project-catalog.json with runtime probe results (It.82e).
 */
final class ProjectCatalogMergeService
{
    public function __construct(
        private ProjectCatalogReader $reader,
        private CatalogDeployStatusResolver $deployStatus,
        private ImplementationChecklistReader $checklistReader,
    ) {
    }

    /**
     * @param list<array{id: string, status: string, message: string, since: string|null, group: string, labelKey: string}> $probes
     *
     * @return array{
     *   schemaVersion: int,
     *   updatedAt: string,
     *   runtime: array{appVersion: string, environment: string},
     *   progress: array{
     *     percent: int,
     *     shipped: int,
     *     partial: int,
     *     planned: int,
     *     total: int,
     *     liveOnInstance: int,
     *     pendingDeploy: int
     *   },
     *   iterations: list<array<string, mixed>>,
     *   timeline: list<array<string, mixed>>,
     *   checklist: array{updatedAt: string, slices: list<array<string, mixed>>}
     * }
     */
    public function merge(array $probes): array
    {
        $catalog = $this->reader->read();
        $probeIndex = [];
        foreach ($probes as $probe) {
            $probeIndex[(string) $probe['id']] = $probe;
        }

        $iterations = [];
        $shippedCount = 0;
        $partialCount = 0;
        $plannedCount = 0;

        foreach ($catalog['iterations'] ?? [] as $iteration) {
            if (!is_array($iteration)) {
                continue;
            }

            $items = [];
            $weightTotal = 0.0;
            $scoreTotal = 0.0;

            foreach ($iteration['items'] ?? [] as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $weight = max(0, (int) ($item['weight'] ?? 1));
                if ($weight === 0) {
                    $items[] = $this->normalizeItem($item, 'deferred', null, 0);
                    continue;
                }

                $probeId = isset($item['probeId']) ? (string) $item['probeId'] : '';
                $phase = (string) ($item['phase'] ?? 'planned');
                $runtimeStatus = $probeId !== '' ? ($probeIndex[$probeId]['status'] ?? null) : null;
                $resolvedStatus = $this->resolveItemStatus($phase, $runtimeStatus);
                $score = $this->scoreForStatus($resolvedStatus);

                $weightTotal += $weight;
                $scoreTotal += $score * $weight;

                $items[] = $this->normalizeItem(
                    $item,
                    $resolvedStatus,
                    $runtimeStatus !== null ? ($probeIndex[$probeId]['message'] ?? null) : null,
                    (int) round($score * 100)
                );
            }

            $percent = $weightTotal > 0 ? (int) round(($scoreTotal / $weightTotal) * 100) : 0;
            $iterationPhase = (string) ($iteration['phase'] ?? 'planned');

            if ($percent >= 100) {
                ++$shippedCount;
            } elseif ($percent > 0 || $iterationPhase === 'partial') {
                ++$partialCount;
            } else {
                ++$plannedCount;
            }

            $iterations[] = [
                'id' => (string) ($iteration['id'] ?? ''),
                'titleKey' => (string) ($iteration['titleKey'] ?? ''),
                'phase' => $iterationPhase,
                'since' => isset($iteration['since']) ? (string) $iteration['since'] : null,
                'targetVersion' => isset($iteration['targetVersion']) ? (string) $iteration['targetVersion'] : null,
                'doc' => (string) ($iteration['doc'] ?? ''),
                'priority' => (string) ($iteration['priority'] ?? 'medium'),
                'percentComplete' => $percent,
                'deployStatus' => $this->deployStatus->resolveForIteration($iteration, $percent),
                'items' => $items,
                'history' => is_array($iteration['history'] ?? null) ? $iteration['history'] : [],
            ];
        }

        $overallPercent = count($iterations) > 0
            ? (int) round(array_sum(array_column($iterations, 'percentComplete')) / count($iterations))
            : 0;

        $runtime = $this->deployStatus->runtimeContext();
        $liveCount = count(array_filter(
            $iterations,
            static fn (array $row): bool => $row['deployStatus'] === 'live'
        ));
        $pendingDeployCount = count(array_filter(
            $iterations,
            static fn (array $row): bool => $row['deployStatus'] === 'pending_deploy'
        ));

        return [
            'schemaVersion' => (int) ($catalog['schemaVersion'] ?? 1),
            'updatedAt' => (string) ($catalog['updatedAt'] ?? ''),
            'runtime' => $runtime,
            'progress' => [
                'percent' => $overallPercent,
                'shipped' => $shippedCount,
                'partial' => $partialCount,
                'planned' => $plannedCount,
                'total' => count($iterations),
                'liveOnInstance' => $liveCount,
                'pendingDeploy' => $pendingDeployCount,
            ],
            'iterations' => $iterations,
            'timeline' => $this->normalizeTimeline($catalog['timeline'] ?? null),
            'checklist' => $this->mergeChecklist($probeIndex),
        ];
    }

    /**
     * @param array<string, array{id: string, status: string, message: string}> $probeIndex
     *
     * @return array{updatedAt: string, slices: list<array<string, mixed>>}
     */
    public function mergeChecklist(array $probeIndex): array
    {
        $doc = $this->checklistReader->read();
        $slices = [];

        foreach ($doc['slices'] ?? [] as $slice) {
            if (!is_array($slice)) {
                continue;
            }

            $items = [];
            $scoreTotal = 0.0;
            $weightTotal = 0.0;

            foreach ($slice['items'] ?? [] as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $weight = 1;
                $weightTotal += $weight;
                $itemStatus = $this->resolveChecklistItemStatus($item, $probeIndex);
                $score = match ($itemStatus) {
                    'done' => 1.0,
                    'partial' => 0.5,
                    default => 0.0,
                };
                $scoreTotal += $score * $weight;

                $items[] = [
                    'id' => (string) ($item['id'] ?? ''),
                    'labelKey' => (string) ($item['labelKey'] ?? ''),
                    'phase' => (string) ($item['phase'] ?? 'pending'),
                    'status' => $itemStatus,
                    'probeId' => isset($item['probeId']) ? (string) $item['probeId'] : null,
                    'issues' => is_array($item['issues'] ?? null) ? $item['issues'] : [],
                ];
            }

            $percent = $weightTotal > 0 ? (int) round(($scoreTotal / $weightTotal) * 100) : 0;
            $slicePhase = (string) ($slice['status'] ?? 'in_progress');

            $slices[] = [
                'id' => (string) ($slice['id'] ?? ''),
                'status' => $slicePhase,
                'catalogIterationIds' => is_array($slice['catalogIterationIds'] ?? null)
                    ? $slice['catalogIterationIds']
                    : [],
                'issues' => is_array($slice['issues'] ?? null) ? $slice['issues'] : [],
                'percentComplete' => $percent,
                'deployStatus' => $this->deployStatus->resolveForIteration([
                    'since' => $slice['since'] ?? null,
                    'targetVersion' => $slice['targetVersion'] ?? null,
                    'phase' => $slicePhase === 'partial_live' ? 'partial' : ($percent >= 100 ? 'shipped' : 'partial'),
                ], $percent),
                'items' => $items,
            ];
        }

        return [
            'updatedAt' => (string) ($doc['updatedAt'] ?? ''),
            'slices' => $slices,
        ];
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, array{id: string, status: string, message: string}> $probeIndex
     */
    private function resolveChecklistItemStatus(array $item, array $probeIndex): string
    {
        $phase = (string) ($item['phase'] ?? 'pending');
        $probeId = isset($item['probeId']) ? (string) $item['probeId'] : '';

        if ($probeId !== '') {
            $probeStatus = $probeIndex[$probeId]['status'] ?? null;

            return match ($probeStatus) {
                'implemented' => 'done',
                'partial' => 'partial',
                default => 'pending',
            };
        }

        if ($phase === 'shipped') {
            return 'done';
        }

        $dependsOn = isset($item['dependsOnVersion']) ? trim((string) $item['dependsOnVersion']) : '';
        if ($dependsOn !== '') {
            return $this->deployStatus->resolveForIteration([
                'since' => $dependsOn,
                'phase' => 'shipped',
            ], 100) === 'live' ? 'done' : 'pending';
        }

        return match ($phase) {
            'required' => 'pending',
            default => 'pending',
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeTimeline(mixed $timeline): array
    {
        if (!is_array($timeline)) {
            return [];
        }

        $entries = [];
        foreach ($timeline as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $entries[] = $entry;
        }

        return $entries;
    }

    private function resolveItemStatus(string $phase, ?string $runtimeStatus): string
    {
        if ($runtimeStatus !== null) {
            return $runtimeStatus;
        }

        return match ($phase) {
            'shipped' => 'implemented',
            'partial' => 'partial',
            'deferred' => 'unknown',
            default => 'missing',
        };
    }

    private function scoreForStatus(string $status): float
    {
        return match ($status) {
            'implemented' => 1.0,
            'partial' => 0.5,
            'missing' => 0.0,
            default => 0.0,
        };
    }

    /**
     * @param array<string, mixed> $item
     *
     * @return array<string, mixed>
     */
    private function normalizeItem(array $item, string $status, ?string $runtimeMessage, int $percent): array
    {
        return [
            'id' => (string) ($item['id'] ?? ''),
            'titleKey' => (string) ($item['titleKey'] ?? ''),
            'probeId' => isset($item['probeId']) ? (string) $item['probeId'] : null,
            'phase' => (string) ($item['phase'] ?? 'planned'),
            'status' => $status,
            'percentComplete' => $percent,
            'runtimeMessage' => $runtimeMessage,
        ];
    }
}
