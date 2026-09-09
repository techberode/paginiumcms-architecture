<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\ProjectPlanner\Models;

final class ProjectPlan
{
    public const SCHEMA_VERSION = 1;

    /** @var list<string> */
    public const STATUSES = ['planned', 'in_progress', 'done', 'skipped', 'blocked'];

    /** @var list<string> */
    public const CONTENT_TYPES = ['page', 'article', 'landing', 'media', 'newsletter', 'custom'];

    /**
     * @param list<ProjectPlanPhase> $phases
     * @param list<ProjectPlanItem> $items
     */
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $description,
        public readonly string $timezone,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly string $createdBy,
        public readonly bool $isDefault,
        public readonly array $phases,
        public readonly array $items,
        public readonly int $schemaVersion = self::SCHEMA_VERSION,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $phases = [];
        foreach ($this->phases as $phase) {
            $phases[] = $phase->toArray();
        }

        $items = [];
        foreach ($this->items as $item) {
            $items[] = $item->toArray();
        }

        return [
            'schemaVersion' => $this->schemaVersion,
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'timezone' => $this->timezone,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'createdBy' => $this->createdBy,
            'isDefault' => $this->isDefault,
            'phases' => $phases,
            'items' => $items,
        ];
    }

    public function withDefault(bool $isDefault): self
    {
        return new self(
            $this->id,
            $this->title,
            $this->description,
            $this->timezone,
            $this->createdAt,
            $this->updatedAt,
            $this->createdBy,
            $isDefault,
            $this->phases,
            $this->items,
            $this->schemaVersion,
        );
    }

    public function withUpdatedAt(string $updatedAt): self
    {
        return new self(
            $this->id,
            $this->title,
            $this->description,
            $this->timezone,
            $this->createdAt,
            $updatedAt,
            $this->createdBy,
            $this->isDefault,
            $this->phases,
            $this->items,
            $this->schemaVersion,
        );
    }
}
