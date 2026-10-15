<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Services;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\FlatFile\Models\Content;
use PaginiumCMS\Modules\Media\Services\MediaUrlResolver;

/**
 * Rewrites stored content/front matter when a media file path changes (rename/move).
 */
final class ContentMediaReferenceRewriter
{
    public function __construct(
        private ContentRepositoryInterface $repository,
        private MediaUrlResolver $mediaUrlResolver,
    ) {
    }

    /**
     * @return array{filesUpdated: int, replacements: int}
     */
    public function rewritePaths(string $fromRelativePath, string $toRelativePath): array
    {
        $fromRelativePath = $this->normalizeMediaPath($fromRelativePath);
        $toRelativePath = $this->normalizeMediaPath($toRelativePath);

        if ($fromRelativePath === $toRelativePath) {
            return ['filesUpdated' => 0, 'replacements' => 0];
        }

        $pairs = $this->replacementPairs($fromRelativePath, $toRelativePath);
        $filesUpdated = 0;
        $replacements = 0;

        foreach (array_merge($this->repository->findAllPages(), $this->repository->findAllArticles()) as $item) {
            $result = $this->rewriteContentItem($item, $pairs);
            if ($result['replacements'] === 0) {
                continue;
            }

            $replacements += $result['replacements'];
            ++$filesUpdated;
            $this->repository->save($item);
        }

        return ['filesUpdated' => $filesUpdated, 'replacements' => $replacements];
    }

    /**
     * @param array<int, array{from: string, to: string}> $pairs
     * @return array{replacements: int}
     */
    private function rewriteContentItem(Content $item, array $pairs): array
    {
        $count = 0;
        $body = $item->getContent();
        $newBody = $this->applyPairs($body, $pairs, $count);

        $frontMatter = $item->getFrontMatter();
        $newFrontMatter = $this->rewriteFrontMatter($frontMatter, $pairs, $count);

        if ($count === 0) {
            return ['replacements' => 0];
        }

        $item->setContent($newBody);
        $item->setFrontMatter($newFrontMatter);

        if ($item instanceof Article) {
            $featured = $item->getFeaturedImage();
            if ($featured !== '') {
                $featuredCount = 0;
                $updated = $this->applyPairs($featured, $pairs, $featuredCount);
                if ($updated !== $featured) {
                    $count += $featuredCount;
                    $item->setFeaturedImage($updated);
                }
            }
        }

        return ['replacements' => $count];
    }

    /**
     * @param array<int|string, mixed> $frontMatter
     * @param array<int, array{from: string, to: string}> $pairs
     * @return array<int|string, mixed>
     */
    private function rewriteFrontMatter(array $frontMatter, array $pairs, int &$count): array
    {
        foreach ($frontMatter as $key => $value) {
            if (is_string($value)) {
                $frontMatter[$key] = $this->applyPairs($value, $pairs, $count);
                continue;
            }
            if (is_array($value)) {
                $frontMatter[$key] = $this->rewriteFrontMatter($value, $pairs, $count);
            }
        }

        return $frontMatter;
    }

    /**
     * @param array<int, array{from: string, to: string}> $pairs
     */
    private function applyPairs(string $text, array $pairs, int &$count): string
    {
        $out = $text;
        foreach ($pairs as $pair) {
            if ($pair['from'] === '' || !str_contains($out, $pair['from'])) {
                continue;
            }
            $before = $out;
            $out = str_replace($pair['from'], $pair['to'], $out);
            if ($out !== $before) {
                ++$count;
            }
        }

        return $out;
    }

    /**
     * @return array<int, array{from: string, to: string}>
     */
    private function replacementPairs(string $fromRelativePath, string $toRelativePath): array
    {
        $fromStorage = $this->mediaUrlResolver->localPublicUrl($fromRelativePath);
        $toStorage = $this->mediaUrlResolver->localPublicUrl($toRelativePath);
        $fromApi = '/api/media/file/' . rawurlencode($fromRelativePath);
        $toApi = '/api/media/file/' . rawurlencode($toRelativePath);

        return [
            ['from' => $fromStorage, 'to' => $toStorage],
            ['from' => $fromApi, 'to' => $toApi],
            ['from' => $fromRelativePath, 'to' => $toRelativePath],
            ['from' => rawurlencode($fromRelativePath), 'to' => rawurlencode($toRelativePath)],
        ];
    }

    private function normalizeMediaPath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'content/')) {
            $path = substr($path, strlen('content/'));
        }

        if (!str_starts_with($path, 'media/')) {
            $path = 'media/' . ltrim($path, '/');
        }

        return $path;
    }
}
