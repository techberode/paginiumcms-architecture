<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\ProjectPlanner\Models;

final class ProjectPlanItemVariance
{
    public const BADGE_NONE = 'none';
    public const BADGE_EARLY = 'early';
    public const BADGE_LATE = 'late';
    public const BADGE_ON_TIME = 'on_time';
    public const BADGE_OVERDUE = 'overdue';
    public const BADGE_DUE_SOON = 'due_soon';

    public function __construct(
        public readonly string $itemId,
        public readonly string $badge,
        public readonly int $days,
    ) {
    }

    /**
     * @return array{itemId: string, badge: string, days: int}
     */
    public function toArray(): array
    {
        return [
            'itemId' => $this->itemId,
            'badge' => $this->badge,
            'days' => $this->days,
        ];
    }
}
