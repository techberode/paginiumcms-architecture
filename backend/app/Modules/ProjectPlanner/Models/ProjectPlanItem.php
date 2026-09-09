<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\ProjectPlanner\Models;

final class ProjectPlanItem
{
    /**
     * @param array{type: string, slug: string|null} $linkedContent
     */
    public function __construct(
        public readonly string $id,
        public readonly ?string $phaseId,
        public readonly string $title,
        public readonly string $contentType,
        public readonly ?string $dueAt,
        public readonly string $status,
        public readonly array $linkedContent,
        public readonly ?string $completedAt,
        public readonly string $notes,
    ) {
    }

    /**
     * @return array{
     *     id: string,
     *     phaseId: string|null,
     *     title: string,
     *     contentType: string,
     *     dueAt: string|null,
     *     status: string,
     *     linkedContent: array{type: string, slug: string|null},
     *     completedAt: string|null,
     *     notes: string
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'phaseId' => $this->phaseId,
            'title' => $this->title,
            'contentType' => $this->contentType,
            'dueAt' => $this->dueAt,
            'status' => $this->status,
            'linkedContent' => $this->linkedContent,
            'completedAt' => $this->completedAt,
            'notes' => $this->notes,
        ];
    }
}
