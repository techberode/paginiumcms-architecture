<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Content\Services;

use PaginiumCMS\Core\FlatFile\Models\ContentIndexEntry;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Http\Support\PaginationQuery;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Support\Lang;

/**
 * Surfaces pending_review content in the admin desk queue for team leaders.
 */
final class ContentEditorialDeskService
{
    private const DESK_KIND = 'content_review';

    public function __construct(
        private ContentEditorialReviewService $review,
        private ContentIndexService $index,
        private SettingsRepositoryInterface $settings,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pendingReviewDeskItems(User $actor): array
    {
        if (!$this->isDeskEnabled() || !$this->review->isWorkflowActive() || !$this->review->isTeamLeader($actor)) {
            return [];
        }

        $items = [];
        foreach (['page', 'article'] as $type) {
            $query = new PaginationQuery(1, 50, '', '-updatedAt', ['status' => ContentEditorialReviewService::STATUS_PENDING]);
            $result = $this->index->query($type, $query);
            foreach ($result['entries'] as $entry) {
                $items[] = $this->presentEntry($entry);
            }
        }

        return $items;
    }

    private function isDeskEnabled(): bool
    {
        $content = $this->settings->group('content');

        return (bool) ($content['editorialReviewDeskEnabled'] ?? true);
    }

    /**
     * @return array<string, mixed>
     */
    private function presentEntry(ContentIndexEntry $entry): array
    {
        $type = $entry->type;
        $slug = $entry->slug;
        $href = $type === 'article' ? '/articles/' . rawurlencode($slug) : '/pages/' . rawurlencode($slug);

        return [
            'kind' => self::DESK_KIND,
            'id' => $type . ':' . $slug,
            'title' => $entry->title !== '' ? $entry->title : $slug,
            'preview' => Lang::get('editorial_review_desk_preview', [
                'type' => $type,
                'slug' => $slug,
            ], 'content'),
            'href' => $href,
            'createdAt' => $entry->updatedAt !== '' ? $entry->updatedAt : $entry->createdAt,
            'handleStatus' => ContentEditorialReviewService::STATUS_PENDING,
            'priorityRank' => 4,
        ];
    }
}
