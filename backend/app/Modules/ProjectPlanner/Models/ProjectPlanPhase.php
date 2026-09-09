<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\ProjectPlanner\Models;

final class ProjectPlanPhase
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly int $sortOrder,
    ) {
    }

    /**
     * @return array{id: string, title: string, sortOrder: int}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'sortOrder' => $this->sortOrder,
        ];
    }
}
