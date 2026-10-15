<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Services;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\FlatFile\Models\Content;
use PaginiumCMS\Core\FlatFile\Models\Page;

/**
 * Verifies that media paths referenced in pages/articles exist on disk.
 */
final class ContentMediaIntegrityService
{
    public function __construct(
        private ContentRepositoryInterface $repository,
        private FileReaderInterface $reader,
        private ContentMediaReferenceExtractor $extractor,
    ) {
    }

    /**
     * @return array{
     *     healthy: bool,
     *     contentItemsScanned: int,
     *     referencesTotal: int,
     *     missingTotal: int,
     *     missing: list<array{contentPath: string, mediaPath: string, field: string}>
     * }
     */
    public function scan(): array
    {
        $missing = [];
        $referencesTotal = 0;
        $items = array_merge(
            $this->repository->findAllPages(),
            $this->repository->findAllArticles(),
        );

        foreach ($items as $item) {
            foreach ($this->collectMissingForContent($item) as $entry) {
                $key = $entry['contentPath'] . '|' . $entry['mediaPath'];
                $missing[$key] = $entry;
            }
        }

        $referencesTotal = 0;
        foreach ($items as $item) {
            $referencesTotal += count($this->collectReferencesForContent($item));
        }

        $missingList = array_values($missing);

        return [
            'healthy' => $missingList === [],
            'contentItemsScanned' => count($items),
            'referencesTotal' => $referencesTotal,
            'missingTotal' => count($missingList),
            'missing' => $missingList,
        ];
    }

    /**
     * @return list<array{contentPath: string, mediaPath: string, field: string}>
     */
    private function collectMissingForContent(Content $item): array
    {
        $missing = [];
        foreach ($this->collectReferencesForContent($item) as $ref) {
            if ($this->mediaExists($ref['mediaPath'])) {
                continue;
            }
            $missing[] = [
                'contentPath' => $item->getPath(),
                'mediaPath' => $ref['mediaPath'],
                'field' => $ref['field'],
            ];
        }

        return $missing;
    }

    /**
     * @return list<array{mediaPath: string, field: string}>
     */
    private function collectReferencesForContent(Content $item): array
    {
        $refs = [];
        $body = $item->getContent();
        foreach ($this->extractor->extractFromText($body) as $path) {
            $refs[] = ['mediaPath' => $path, 'field' => 'body'];
        }

        foreach ($this->extractor->extractFromFrontMatter($item->getFrontMatter()) as $path) {
            $refs[] = ['mediaPath' => $path, 'field' => 'frontMatter'];
        }

        if ($item instanceof Article) {
            $featured = $this->extractor->toContentRelativePath($item->getFeaturedImage());
            if ($featured !== null) {
                $refs[] = ['mediaPath' => $featured, 'field' => 'featuredImage'];
            }
        }

        if ($item instanceof Page) {
            $hero = $item->getFrontMatter()['heroImage'] ?? $item->getFrontMatter()['hero_image'] ?? null;
            if (is_string($hero)) {
                $heroPath = $this->extractor->toContentRelativePath($hero);
                if ($heroPath !== null) {
                    $refs[] = ['mediaPath' => $heroPath, 'field' => 'heroImage'];
                }
            }
        }

        $dedup = [];
        foreach ($refs as $ref) {
            $key = $ref['mediaPath'] . '|' . $ref['field'];
            $dedup[$key] = $ref;
        }

        return array_values($dedup);
    }

    private function mediaExists(string $contentRelativePath): bool
    {
        if (str_contains($contentRelativePath, '..')) {
            return false;
        }

        try {
            return $this->reader->exists($contentRelativePath);
        } catch (\Throwable) {
            return false;
        }
    }
}
