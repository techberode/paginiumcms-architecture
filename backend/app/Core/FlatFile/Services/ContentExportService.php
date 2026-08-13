<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Services;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\FlatFile\Models\Content;
use PaginiumCMS\Core\FlatFile\Models\Page;
use PaginiumCMS\Support\AppVersion;

/**
 * Serializes flat-file pages/articles for CLI export (It.80f).
 */
final class ContentExportService
{
    public const SCHEMA_VERSION = 1;

    public function __construct(
        private ContentRepositoryInterface $repository,
    ) {
    }

    /**
     * @return array{
     *     schemaVersion: int,
     *     exportedAt: string,
     *     cmsVersion: string,
     *     items: list<array{type: string, slug: string, frontMatter: array<string, mixed>, content: string}>
     * }
     */
    public function buildPayload(?string $typeFilter = null): array
    {
        $items = [];

        if ($typeFilter === null || $typeFilter === 'page') {
            foreach ($this->repository->findAllPages() as $page) {
                $items[] = $this->serializeItem($page, 'page');
            }
        }

        if ($typeFilter === null || $typeFilter === 'article') {
            foreach ($this->repository->findAllArticles() as $article) {
                $items[] = $this->serializeItem($article, 'article');
            }
        }

        return [
            'schemaVersion' => self::SCHEMA_VERSION,
            'exportedAt' => gmdate('c'),
            'cmsVersion' => AppVersion::current(),
            'items' => $items,
        ];
    }

    /**
     * @return array{type: string, slug: string, frontMatter: array<string, mixed>, content: string}
     */
    private function serializeItem(Content $content, string $type): array
    {
        /** @var array<string, mixed> $frontMatter */
        $frontMatter = [];
        foreach ($content->getFrontMatter() as $key => $value) {
            if (is_string($key)) {
                $frontMatter[$key] = $value;
            }
        }

        return [
            'type' => $type,
            'slug' => $content->getSlug(),
            'frontMatter' => $frontMatter,
            'content' => $content->getContent(),
        ];
    }
}
