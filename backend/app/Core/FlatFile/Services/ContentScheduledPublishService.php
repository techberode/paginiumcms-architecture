<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Services;

use DateTimeImmutable;
use PaginiumCMS\Core\Cache\ContentCacheService;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Models\Content;
use PaginiumCMS\Core\Versioning\Services\ContentVersioningService;
use PaginiumCMS\Core\Workflow\Services\OtpWorkflowService;
use PaginiumCMS\Support\AppTimezone;

/**
 * Publishes content items whose scheduledAt has passed (Iteration 59).
 */
class ContentScheduledPublishService
{
    public function __construct(
        private ContentRepositoryInterface $repository,
        private ContentVersioningService $versioning,
        private ContentCacheService $contentCache,
        private OtpWorkflowService $otpWorkflow
    ) {
    }

    /**
     * @return array{
     *     published: list<array{type: string, slug: string}>,
     *     skipped: list<array{type: string, slug: string, reason: string}>
     * }
     */
    public function publishDueItems(?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable(AppTimezone::now());
        $published = [];
        $skipped = [];

        foreach ($this->loadScheduledItems('page') as $content) {
            $this->collectResult($this->tryPublishOne($content, 'page', $now), $content, 'page', $published, $skipped);
        }

        foreach ($this->loadScheduledItems('article') as $content) {
            $this->collectResult($this->tryPublishOne($content, 'article', $now), $content, 'article', $published, $skipped);
        }

        return [
            'published' => $published,
            'skipped' => $skipped,
        ];
    }

    /**
     * @return array{published: bool, reason: string}
     */
    private function tryPublishOne(Content $content, string $type, DateTimeImmutable $now): array
    {
        if ($content->isPublished()) {
            return ['published' => false, 'reason' => 'already_published'];
        }

        $scheduledAt = $content->getScheduledAt();
        if ($scheduledAt === null) {
            return ['published' => false, 'reason' => 'missing_scheduled_at'];
        }

        if ($scheduledAt > $now) {
            return ['published' => false, 'reason' => 'not_due'];
        }

        if ($this->otpWorkflow->isPublishApprovalOtpEnabled()) {
            $approvedAt = $content->getFrontMatter()['publishApprovedAt'] ?? null;
            if (!is_string($approvedAt) || trim($approvedAt) === '') {
                return ['published' => false, 'reason' => 'otp_not_approved'];
            }
        }

        try {
            $content->setStatus('published');
            $content->clearSchedulingMetadata();
            $this->repository->save($content);
            $this->versioning->recordChange($content, $type, 'scheduled_publish');
            $this->invalidateCache($type, $content->getSlug());
        } catch (FlatFileException $e) {
            return ['published' => false, 'reason' => 'save_failed'];
        }

        return ['published' => true, 'reason' => 'published'];
    }

    /**
     * @param array{published: bool, reason: string} $result
     * @param list<array{type: string, slug: string}> $published
     * @param list<array{type: string, slug: string, reason: string}> $skipped
     */
    private function collectResult(
        array $result,
        Content $content,
        string $type,
        array &$published,
        array &$skipped
    ): void {
        if ($result['published']) {
            $published[] = ['type' => $type, 'slug' => $content->getSlug()];

            return;
        }

        if ($result['reason'] === 'not_due' || $result['reason'] === 'already_published') {
            return;
        }

        $skipped[] = [
            'type' => $type,
            'slug' => $content->getSlug(),
            'reason' => $result['reason'],
        ];
    }

    /**
     * @return list<Content>
     */
    private function loadScheduledItems(string $type): array
    {
        $items = $type === 'article'
            ? $this->repository->findAllArticles(['status' => 'scheduled'])
            : $this->repository->findAllPages(['status' => 'scheduled']);

        return array_values($items);
    }

    private function invalidateCache(string $type, string $slug): void
    {
        if ($type === 'page') {
            $this->contentCache->invalidatePage($slug);

            return;
        }

        $this->contentCache->invalidateArticle($slug);
    }
}
