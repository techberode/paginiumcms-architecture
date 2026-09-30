<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Content\Services;

use PaginiumCMS\Core\Notification\Services\IncidentNotifier;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Teams\Services\TeamRepository;
use PaginiumCMS\Support\Lang;

/**
 * Notifies operators when content enters pending_review (editorial workflow).
 */
final class ContentEditorialReviewNotificationService
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private TeamRepository $teams,
        private IncidentNotifier $notifier,
        private ContentEditorialReviewService $review,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function handleAfterSave(array $context): void
    {
        if (!$this->review->isWorkflowActive()) {
            return;
        }

        if (($context['status'] ?? '') !== ContentEditorialReviewService::STATUS_PENDING) {
            return;
        }

        if (($context['previousStatus'] ?? '') === ContentEditorialReviewService::STATUS_PENDING) {
            return;
        }

        if (!$this->notifyEnabled()) {
            return;
        }

        $type = (string) ($context['type'] ?? '');
        $slug = trim((string) ($context['slug'] ?? ''));
        $title = trim((string) ($context['title'] ?? $slug));
        if ($slug === '') {
            return;
        }

        if ($this->collectLeaderUserIds() === []) {
            return;
        }

        $subject = Lang::get('editorial_review_notify_subject', ['title' => $title], 'content');
        $body = Lang::get('editorial_review_notify_body', [
            'title' => $title,
            'type' => $type,
            'slug' => $slug,
        ], 'content');

        $this->notifier->notifyViaConnectorDetailed(
            $this->connector(),
            'content.editorial.pending_review',
            $subject,
            $body,
            'info'
        );
    }

    private function notifyEnabled(): bool
    {
        $content = $this->settings->group('content');
        if (!(bool) ($content['editorialReviewNotifyLeaders'] ?? true)) {
            return false;
        }

        $monitoring = $this->settings->group('monitoring');

        return (bool) ($monitoring['contentPublishNotifyEnabled'] ?? false);
    }

    private function connector(): string
    {
        $monitoring = $this->settings->group('monitoring');
        $connector = (string) ($monitoring['contentPublishConnector'] ?? 'email');

        return $connector !== '' ? $connector : 'email';
    }

    /**
     * @return list<string>
     */
    private function collectLeaderUserIds(): array
    {
        $ids = [];
        foreach ($this->teams->list() as $team) {
            $leaders = is_array($team['teamLeaderUserIds'] ?? null) ? $team['teamLeaderUserIds'] : [];
            foreach ($leaders as $leaderId) {
                if (is_string($leaderId) && $leaderId !== '') {
                    $ids[$leaderId] = true;
                }
            }
        }

        return array_keys($ids);
    }
}
