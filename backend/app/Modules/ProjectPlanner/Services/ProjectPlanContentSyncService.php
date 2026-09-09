<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\ProjectPlanner\Services;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Modules\ProjectPlanner\Contracts\ProjectPlanRepositoryInterface;

/**
 * Marks plan items done when linked page/article is published (It.87i).
 */
final class ProjectPlanContentSyncService
{
    /** @var list<string> */
    private const LINKABLE_TYPES = ['page', 'article'];

    public function __construct(
        private ProjectPlanRepositoryInterface $plans,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function handleStatusChange(array $context): void
    {
        if (($context['status'] ?? '') !== 'published') {
            return;
        }
        if (($context['previousStatus'] ?? '') === 'published') {
            return;
        }

        $this->markLinkedItemsDone($context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function handleScheduledPublish(array $context): void
    {
        $this->markLinkedItemsDone($context);
    }

    /**
     * Covers create/PUT that persist status=published without a separate status-change hook.
     *
     * @param array<string, mixed> $context
     */
    public function handleAfterSave(array $context): void
    {
        if (($context['status'] ?? '') !== 'published') {
            return;
        }

        $this->markLinkedItemsDone($context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function markLinkedItemsDone(array $context): void
    {
        $type = $this->normalizeType((string) ($context['type'] ?? ''));
        $slug = trim((string) ($context['slug'] ?? ''));
        if ($slug === '' || !in_array($type, self::LINKABLE_TYPES, true)) {
            return;
        }

        $completedAt = $this->resolveCompletedAt($context);

        foreach ($this->plans->findAll() as $plan) {
            foreach ($plan->items as $item) {
                if ($item->status === 'done' || $item->status === 'skipped') {
                    continue;
                }
                $linkedType = $this->normalizeType($item->linkedContent['type']);
                $linkedSlug = trim((string) ($item->linkedContent['slug'] ?? ''));
                if ($linkedType !== $type || $linkedSlug !== $slug) {
                    continue;
                }

                try {
                    $this->plans->updateItem($plan->id, $item->id, [
                        'status' => 'done',
                        'completedAt' => $completedAt,
                    ]);
                } catch (FlatFileException) {
                    continue;
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    private function resolveCompletedAt(array $context): string
    {
        $raw = $context['completedAt'] ?? null;
        if (is_string($raw) && trim($raw) !== '') {
            return trim($raw);
        }

        return date('c');
    }

    private function normalizeType(string $raw): string
    {
        $type = strtolower(trim($raw));

        return match ($type) {
            'pages', 'page' => 'page',
            'articles', 'article' => 'article',
            default => $type,
        };
    }
}
