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
        private OriginCatalogLabelResolver $labels,
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
     *   snapshot: array<string, mixed>,
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
        $progressPercents = [];
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
            $declaredPhase = (string) ($iteration['phase'] ?? 'planned');
            $iterationPhase = $percent >= 100
                ? 'shipped'
                : ($percent > 0 || $declaredPhase === 'partial' ? 'partial' : 'planned');

            $countsTowardProgress = !str_starts_with((string) ($iteration['id'] ?? ''), 'ops.');
            if ($countsTowardProgress) {
                $progressPercents[] = $percent;
                if ($percent >= 100) {
                    ++$shippedCount;
                } elseif ($percent > 0 || $iterationPhase === 'partial') {
                    ++$partialCount;
                } else {
                    ++$plannedCount;
                }
            }

            $iterationTitleKey = (string) ($iteration['titleKey'] ?? '');

            $iterations[] = [
                'id' => (string) ($iteration['id'] ?? ''),
                'titleKey' => $iterationTitleKey,
                'titleLabel' => $this->labels->resolve($iterationTitleKey),
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

        $overallPercent = count($progressPercents) > 0
            ? (int) round(array_sum($progressPercents) / count($progressPercents))
            : 0;

        $runtime = $this->deployStatus->runtimeContext();
        $catalogIterations = array_values(array_filter(
            $iterations,
            static fn (array $row): bool => !str_starts_with((string) $row['id'], 'ops.')
        ));
        $liveCount = count(array_filter(
            $catalogIterations,
            static fn (array $row): bool => $row['deployStatus'] === 'live'
        ));
        $pendingDeployCount = count(array_filter(
            $catalogIterations,
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
                'total' => count($progressPercents),
                'liveOnInstance' => $liveCount,
                'pendingDeploy' => $pendingDeployCount,
            ],
            'iterations' => $iterations,
            'timeline' => $this->normalizeTimeline($catalog['timeline'] ?? null),
            'snapshot' => $this->normalizeSnapshot($catalog['snapshot'] ?? null, (string) ($catalog['updatedAt'] ?? '')),
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

                $labelKey = (string) ($item['labelKey'] ?? '');

                $items[] = [
                    'id' => (string) ($item['id'] ?? ''),
                    'labelKey' => $labelKey,
                    'labelLabel' => $this->labels->resolve($labelKey),
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
     * Maintainer “as of today” overview — not a new iteration number.
     *
     * @return array{
     *   asOf: string,
     *   latestTag: string,
     *   headlineKey: string,
     *   headlineLabel: string,
     *   groups: list<array{id: string, titleKey: string, titleLabel: string, items: list<array{titleKey: string, titleLabel: string, noteKey: string|null, noteLabel: string|null}>}>
     * }
     */
    private function normalizeSnapshot(mixed $raw, string $fallbackAsOf): array
    {
        $asOf = $fallbackAsOf;
        $latestTag = '';
        $headlineKey = 'origin.snapshot.headline';
        $groupsIn = [];

        if (is_array($raw)) {
            $asOf = trim((string) ($raw['asOf'] ?? $fallbackAsOf));
            $latestTag = trim((string) ($raw['latestTag'] ?? ''));
            $headlineKey = trim((string) ($raw['headlineKey'] ?? $headlineKey));
            $groupsIn = is_array($raw['groups'] ?? null) ? $raw['groups'] : [];
        }

        $groups = [];
        foreach ($groupsIn as $group) {
            if (!is_array($group)) {
                continue;
            }

            $titleKey = (string) ($group['titleKey'] ?? '');
            $items = [];
            foreach ($group['items'] ?? [] as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $titleItemKey = (string) ($item['titleKey'] ?? '');
                $noteKey = isset($item['noteKey']) ? trim((string) $item['noteKey']) : '';
                $items[] = [
                    'titleKey' => $titleItemKey,
                    'titleLabel' => $this->labels->resolve($titleItemKey),
                    'noteKey' => $noteKey !== '' ? $noteKey : null,
                    'noteLabel' => $noteKey !== '' ? $this->labels->resolve($noteKey) : null,
                ];
            }

            $groups[] = [
                'id' => (string) ($group['id'] ?? ''),
                'titleKey' => $titleKey,
                'titleLabel' => $this->labels->resolve($titleKey),
                'items' => $items,
            ];
        }

        return [
            'asOf' => $asOf !== '' ? $asOf : $fallbackAsOf,
            'latestTag' => $latestTag,
            'headlineKey' => $headlineKey,
            'headlineLabel' => $this->labels->resolve($headlineKey),
            'groups' => $groups,
        ];
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

            $summaryKey = (string) ($entry['summaryKey'] ?? '');

            $entries[] = [
                ...$entry,
                'summaryKey' => $summaryKey,
                'summaryLabel' => $this->labels->resolve($summaryKey),
            ];
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
        $titleKey = (string) ($item['titleKey'] ?? '');

        return [
            'id' => (string) ($item['id'] ?? ''),
            'titleKey' => $titleKey,
            'titleLabel' => $this->labels->resolve($titleKey),
            'probeId' => isset($item['probeId']) ? (string) $item['probeId'] : null,
            'phase' => (string) ($item['phase'] ?? 'planned'),
            'status' => $status,
            'percentComplete' => $percent,
            'runtimeMessage' => $runtimeMessage,
        ];
    }
}
