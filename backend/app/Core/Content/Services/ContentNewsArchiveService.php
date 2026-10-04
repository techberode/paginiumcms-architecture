<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Content\Services;

use DateTimeImmutable;
use PaginiumCMS\Core\Cache\ContentCacheService;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\Versioning\Services\ContentVersioningService;
use PaginiumCMS\Support\AppTimezone;

/**
 * Moves published News articles to Archive category after retention (scheduler + manual).
 */
class ContentNewsArchiveService
{
    public function __construct(
        private ContentRepositoryInterface $repository,
        private NewsArchivePolicy $policy,
        private ContentVersioningService $versioning,
        private ContentCacheService $contentCache,
    ) {
    }

    /**
     * @return array{
     *     archived: list<array{slug: string}>,
     *     skipped: list<array{slug: string, reason: string}>
     * }
     */
    public function archiveDueNewsArticles(?DateTimeImmutable $now = null): array
    {
        if (!$this->policy->autoArchiveEnabled()) {
            return ['archived' => [], 'skipped' => []];
        }

        $now ??= new DateTimeImmutable('today', new \DateTimeZone(AppTimezone::current()));
        $archived = [];
        $skipped = [];

        foreach ($this->repository->findAllArticles(['status' => 'published', 'category' => NewsArchivePolicy::CATEGORY_NEWS]) as $article) {
            if (!$this->policy->isNewsArchiveDue($article, $now)) {
                $skipped[] = ['slug' => $article->getSlug(), 'reason' => 'not_due'];
                continue;
            }

            if ($this->moveArticleToArchive($article, 'scheduler')) {
                $archived[] = ['slug' => $article->getSlug()];
            } else {
                $skipped[] = ['slug' => $article->getSlug(), 'reason' => 'save_failed'];
            }
        }

        return ['archived' => $archived, 'skipped' => $skipped];
    }

    public function moveArticleToArchiveBySlug(string $slug): bool
    {
        $article = $this->repository->findBySlug($slug, 'article');
        if (!$article instanceof Article) {
            return false;
        }

        return $this->moveArticleToArchive($article, 'manual');
    }

    private function moveArticleToArchive(Article $article, string $source): bool
    {
        if (mb_strtolower($article->getCategory()) === NewsArchivePolicy::CATEGORY_ARCHIVE) {
            return true;
        }

        try {
            $article->setCategory(NewsArchivePolicy::CATEGORY_ARCHIVE);
            $frontMatter = $article->getFrontMatter();
            $frontMatter['newsArchivedAt'] = (new DateTimeImmutable('now', new \DateTimeZone(AppTimezone::current())))->format('c');
            $frontMatter['newsArchiveSource'] = $source;
            $article->setFrontMatter($frontMatter);
            $this->repository->save($article);
            $this->versioning->recordChange($article, 'article', 'news_archive');
            $this->contentCache->invalidateArticle($article->getSlug());

            return true;
        } catch (FlatFileException) {
            return false;
        }
    }
}
