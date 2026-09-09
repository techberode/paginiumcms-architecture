<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\ProjectPlanner\Services;

use PaginiumCMS\Modules\ProjectPlanner\Models\ProjectPlan;
use PaginiumCMS\Modules\ProjectPlanner\Models\ProjectPlanProgress;

/**
 * JSON shapes for the project-plan admin API (It.87f).
 */
final class ProjectPlanApiPresenter
{
    public function __construct(
        private ProjectPlanProgressService $progress,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(ProjectPlan $plan): array
    {
        $progress = $this->progress->summarize($plan);
        $data = $plan->toArray();
        $data['progress'] = $this->progressSummary($progress);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(ProjectPlan $plan): array
    {
        $data = $plan->toArray();
        $data['progress'] = $this->progress->summarize($plan)->toArray();

        return $data;
    }

    /**
     * @param list<ProjectPlan> $plans
     *
     * @return array<string, mixed>
     */
    public function overview(array $plans): array
    {
        $planCount = count($plans);
        $counted = 0;
        $score = 0.0;
        $doneCount = 0;
        $overdueCount = 0;
        $dueSoonCount = 0;
        $deadlines = [];

        foreach ($plans as $plan) {
            $progress = $this->progress->summarize($plan);
            $counted += $progress->countedItems;
            $doneCount += $progress->doneCount;
            $overdueCount += $progress->overdueCount;
            $dueSoonCount += $progress->dueSoonCount;
            $score += ($progress->percent / 100) * $progress->countedItems;

            foreach ($plan->items as $item) {
                if ($item->dueAt === null || $item->status === 'done' || $item->status === 'skipped') {
                    continue;
                }
                $variance = $progress->itemVariances[$item->id];
                $deadlines[] = [
                    'planId' => $plan->id,
                    'planTitle' => $plan->title,
                    'itemId' => $item->id,
                    'title' => $item->title,
                    'contentType' => $item->contentType,
                    'dueAt' => $item->dueAt,
                    'badge' => $variance->badge,
                    'days' => $variance->days,
                ];
            }
        }

        usort(
            $deadlines,
            static fn (array $a, array $b): int => strcmp((string) $a['dueAt'], (string) $b['dueAt'])
        );

        return [
            'planCount' => $planCount,
            'percent' => $counted > 0 ? (int) round(($score / $counted) * 100) : 0,
            'countedItems' => $counted,
            'doneCount' => $doneCount,
            'overdueCount' => $overdueCount,
            'dueSoonCount' => $dueSoonCount,
            'nextDeadlines' => array_slice($deadlines, 0, 3),
        ];
    }

    /**
     * @return array{
     *     percent: int,
     *     countedItems: int,
     *     doneCount: int,
     *     overdueCount: int,
     *     dueSoonCount: int
     * }
     */
    private function progressSummary(ProjectPlanProgress $progress): array
    {
        return [
            'percent' => $progress->percent,
            'countedItems' => $progress->countedItems,
            'doneCount' => $progress->doneCount,
            'overdueCount' => $progress->overdueCount,
            'dueSoonCount' => $progress->dueSoonCount,
        ];
    }
}
