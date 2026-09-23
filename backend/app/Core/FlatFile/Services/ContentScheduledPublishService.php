<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Services;

use DateTimeImmutable;
use PaginiumCMS\Core\Cache\ContentCacheService;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Models\Content;
use PaginiumCMS\Core\Hook\HookCatalog;
use PaginiumCMS\Core\Hook\Services\HookEmitter;
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
        private OtpWorkflowService $otpWorkflow,
        private HookEmitter $hookEmitter
    ) {
    }

    /**
     * @return array{
     *     published: list<array{type: string, slug: string}>,
     *     skipped: list<array{type: string, slug: string, reason: string}>,
     *     diagnostics: array{
     *         now: string,
     *         queue_count: int,
     *         inspected: list<array{
     *             type: string,
     *             slug: string,
     *             status: string,
     *             localeStatus: array<string, string>,
     *             scheduledAt: string|null,
     *             in_queue: bool,
     *             blocking_reason: string
     *         }>
     *     }
     * }
     */
    public function publishDueItems(?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable('now', new \DateTimeZone(AppTimezone::current()));
        $published = [];
        $skipped = [];

        $pageQueue = $this->loadScheduledItems('page');
        $articleQueue = $this->loadScheduledItems('article');

        foreach ($pageQueue as $content) {
            $this->collectResult($this->tryPublishOne($content, 'page', $now), $content, 'page', $published, $skipped);
        }

        foreach ($articleQueue as $content) {
            $this->collectResult($this->tryPublishOne($content, 'article', $now), $content, 'article', $published, $skipped);
        }

        return [
            'published' => $published,
            'skipped' => $skipped,
            'diagnostics' => $this->buildPublishDiagnostics($now, $pageQueue, $articleQueue),
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

        if ($content->getStatus() === 'archived') {
            return ['published' => false, 'reason' => 'archived'];
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
            $scheduledAtIso = $scheduledAt->format('c');
            $this->applyScheduledPublish($content);
            $this->repository->save($content);
            $this->versioning->recordChange($content, $type, 'scheduled_publish');
            $this->invalidateCache($type, $content->getSlug());
            $this->hookEmitter->emit(HookCatalog::CONTENT_AFTER_SCHEDULED_PUBLISH, [
                'type' => $type,
                'slug' => $content->getSlug(),
                'scheduledAt' => $scheduledAtIso,
            ]);
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
            ? $this->repository->findAllArticles()
            : $this->repository->findAllPages();

        return array_values(array_filter(
            $items,
            function (Content $content): bool {
                if ($content->isPublished() || $content->getStatus() === 'archived') {
                    return false;
                }

                if ($content->getScheduledAt() !== null) {
                    return true;
                }

                return $this->hasSchedulableScope($content);
            }
        ));
    }

    private function hasSchedulableScope(Content $content): bool
    {
        if ($content->isPublished()) {
            return false;
        }

        if ($content->isScheduled()) {
            return true;
        }

        $frontMatter = $content->getFrontMatter();
        $localeStatus = $frontMatter['localeStatus'] ?? null;
        if (!is_array($localeStatus)) {
            return false;
        }

        foreach ($localeStatus as $state) {
            if ((string) $state === 'scheduled') {
                return true;
            }
        }

        return false;
    }

    private function applyScheduledPublish(Content $content): void
    {
        $frontMatter = $content->getFrontMatter();
        $localeStatus = $frontMatter['localeStatus'] ?? null;
        if (is_array($localeStatus)) {
            foreach ($localeStatus as $locale => $state) {
                if ((string) $state === 'scheduled') {
                    $localeStatus[(string) $locale] = 'published';
                }
            }
            $frontMatter['localeStatus'] = $localeStatus;
            $content->setFrontMatter($frontMatter);
        }

        $content->setStatus('published');
        $content->clearSchedulingMetadata();
    }

    /**
     * @param list<Content> $pageQueue
     * @param list<Content> $articleQueue
     * @return array{
     *     now: string,
     *     queue_count: int,
     *     inspected: list<array{
     *         type: string,
     *         slug: string,
     *         status: string,
     *         localeStatus: array<string, string>,
     *         scheduledAt: string|null,
     *         in_queue: bool,
     *         blocking_reason: string
     *     }>
     * }
     */
    private function buildPublishDiagnostics(
        DateTimeImmutable $now,
        array $pageQueue,
        array $articleQueue
    ): array {
        $queuedKeys = [];
        foreach (array_merge($pageQueue, $articleQueue) as $content) {
            $queuedKeys[$content->getSlug()] = true;
        }

        $inspected = [];
        foreach (['page' => $this->repository->findAllPages(), 'article' => $this->repository->findAllArticles()] as $type => $items) {
            foreach ($items as $content) {
                if (!$this->shouldInspectForDiagnostics($content)) {
                    continue;
                }

                $frontMatter = $content->getFrontMatter();
                /** @var array<string, string> $localeStatus */
                $localeStatus = is_array($frontMatter['localeStatus'] ?? null) ? $frontMatter['localeStatus'] : [];
                $scheduledRaw = $frontMatter['scheduledAt'] ?? null;
                $scheduledAt = is_string($scheduledRaw) && trim($scheduledRaw) !== '' ? trim($scheduledRaw) : null;

                $inspected[] = [
                    'type' => $type,
                    'slug' => $content->getSlug(),
                    'status' => $content->getStatus(),
                    'localeStatus' => $localeStatus,
                    'scheduledAt' => $scheduledAt,
                    'in_queue' => isset($queuedKeys[$content->getSlug()]),
                    'blocking_reason' => $this->describeBlockingReason($content, $now),
                ];
            }
        }

        return [
            'now' => $now->format('c'),
            'queue_count' => count($pageQueue) + count($articleQueue),
            'inspected' => $inspected,
        ];
    }

    private function shouldInspectForDiagnostics(Content $content): bool
    {
        $frontMatter = $content->getFrontMatter();
        if (!empty($frontMatter['scheduledAt'])) {
            return true;
        }

        if ($content->isScheduled()) {
            return true;
        }

        $localeStatus = $frontMatter['localeStatus'] ?? null;
        if (!is_array($localeStatus)) {
            return false;
        }

        foreach ($localeStatus as $state) {
            if ((string) $state === 'scheduled') {
                return true;
            }
        }

        return false;
    }

    private function describeBlockingReason(Content $content, DateTimeImmutable $now): string
    {
        if ($content->isPublished()) {
            return 'already_published';
        }

        $scheduledAt = $content->getScheduledAt();
        if ($scheduledAt === null) {
            return $this->hasSchedulableScope($content) ? 'missing_scheduled_at' : 'not_scheduled';
        }

        if ($scheduledAt > $now) {
            return 'not_due';
        }

        if (!$this->hasSchedulableScope($content)) {
            return 'not_scheduled';
        }

        if ($this->otpWorkflow->isPublishApprovalOtpEnabled()) {
            $approvedAt = $content->getFrontMatter()['publishApprovedAt'] ?? null;
            if (!is_string($approvedAt) || trim($approvedAt) === '') {
                return 'otp_not_approved';
            }
        }

        return 'ready';
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
