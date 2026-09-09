<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\ProjectPlanner\Models;

final class ProjectPlanProgress
{
    /**
     * @param array<string, ProjectPlanItemVariance> $itemVariances
     */
    public function __construct(
        public readonly int $percent,
        public readonly int $totalItems,
        public readonly int $countedItems,
        public readonly int $doneCount,
        public readonly int $overdueCount,
        public readonly int $dueSoonCount,
        public readonly int $earlyCount,
        public readonly int $lateCount,
        public readonly array $itemVariances,
    ) {
    }

    /**
     * @return array{
     *     percent: int,
     *     totalItems: int,
     *     countedItems: int,
     *     doneCount: int,
     *     overdueCount: int,
     *     dueSoonCount: int,
     *     earlyCount: int,
     *     lateCount: int,
     *     itemVariances: list<array{itemId: string, badge: string, days: int}>
     * }
     */
    public function toArray(): array
    {
        $variances = [];
        foreach ($this->itemVariances as $variance) {
            $variances[] = $variance->toArray();
        }

        return [
            'percent' => $this->percent,
            'totalItems' => $this->totalItems,
            'countedItems' => $this->countedItems,
            'doneCount' => $this->doneCount,
            'overdueCount' => $this->overdueCount,
            'dueSoonCount' => $this->dueSoonCount,
            'earlyCount' => $this->earlyCount,
            'lateCount' => $this->lateCount,
            'itemVariances' => $variances,
        ];
    }
}
