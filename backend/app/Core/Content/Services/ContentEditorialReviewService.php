<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Content\Services;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Teams\Services\TeamRepository;
use PaginiumCMS\Modules\ProjectPlanner\Contracts\ProjectPlanRepositoryInterface;
use PaginiumCMS\Modules\ProjectPlanner\Models\ProjectPlan;
use PaginiumCMS\Modules\Security\Models\User;

/**
 * Team editorial gate: non-leaders cannot publish until reviewed (when workflow is active).
 */
final class ContentEditorialReviewService
{
    public const STATUS_PENDING = 'pending_review';
    public const STATUS_REVIEWED = 'reviewed';

    public function __construct(
        private SettingsRepositoryInterface $settings,
        private TeamRepository $teams,
        private ProjectPlanRepositoryInterface $plans,
    ) {
    }

    public function isWorkflowActive(): bool
    {
        $content = $this->settings->group('content');
        if (!(bool) ($content['editorialReviewEnabled'] ?? false)) {
            return false;
        }

        return count($this->teams->list()) > 0;
    }

    public function areReviewStatusesEnabled(): bool
    {
        if (!$this->isWorkflowActive()) {
            return false;
        }

        $content = $this->settings->group('content');

        return (bool) ($content['editorialReviewStatusesEnabled'] ?? true);
    }

    public function isTeamLeader(User $user): bool
    {
        $userId = $user->getId();
        foreach ($this->teams->list() as $team) {
            $leaders = is_array($team['teamLeaderUserIds'] ?? null) ? $team['teamLeaderUserIds'] : [];
            if (in_array($userId, $leaders, true)) {
                return true;
            }
        }

        return false;
    }

    public function resolveRequestedStatus(User $user, string $requested, string $previous): string
    {
        if (!$this->isWorkflowActive()) {
            return $requested;
        }

        if ($this->isTeamLeader($user)) {
            return $requested;
        }

        if ($requested === 'published') {
            if ($previous === self::STATUS_REVIEWED || $previous === 'published') {
                return 'published';
            }

            return self::STATUS_PENDING;
        }

        return $requested;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function handleAfterSave(array $context): void
    {
        if (($context['status'] ?? '') !== self::STATUS_PENDING) {
            return;
        }

        $type = (string) ($context['type'] ?? '');
        $slug = trim((string) ($context['slug'] ?? ''));
        if ($slug === '' || !in_array($type, ['page', 'article'], true)) {
            return;
        }

        $title = trim((string) ($context['title'] ?? $slug));
        $this->ensurePlannerItem($type, $slug, $title);
    }

    private function ensurePlannerItem(string $type, string $slug, string $title): void
    {
        foreach ($this->plans->findAll() as $plan) {
            foreach ($plan->items as $item) {
                $linkedType = $item->linkedContent['type'];
                $linkedSlug = trim((string) ($item->linkedContent['slug'] ?? ''));
                if ($linkedType === $type && $linkedSlug === $slug && $item->status !== 'done') {
                    return;
                }
            }
        }

        $plan = $this->resolveTargetPlan();
        if ($plan === null) {
            $plan = $this->plans->create([
                'id' => 'content-publication-queue',
                'title' => 'Content publication',
                'description' => 'Auto queue for editorial review workflow.',
                'isDefault' => true,
                'phases' => [
                    ['id' => 'review', 'title' => 'Review', 'sortOrder' => 1],
                ],
                'items' => [],
            ]);
        }

        $itemTitle = $type === 'article' ? 'Review article: ' . $title : 'Review page: ' . $title;
        $this->plans->addItem($plan->id, [
            'title' => $itemTitle,
            'phaseId' => 'review',
            'contentType' => $type,
            'status' => 'planned',
            'linkedContent' => [
                'type' => $type,
                'slug' => $slug,
            ],
            'notes' => 'Auto-created from editorial review workflow.',
        ]);
    }

    private function resolveTargetPlan(): ?ProjectPlan
    {
        $content = $this->settings->group('content');
        $configuredId = trim((string) ($content['editorialReviewPlanId'] ?? ''));
        if ($configuredId !== '') {
            $configured = $this->plans->findById($configuredId);
            if ($configured !== null) {
                return $configured;
            }
        }

        return $this->plans->findDefault() ?? ($this->plans->findAll()[0] ?? null);
    }
}
