<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Import;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;

/**
 * Imports Grav CMS pages from user/pages Markdown tree (It.80g phase 2).
 *
 * @phpstan-import-type NormalizedImportRow from ImportRowTypes
 */
final class GravPagesImporter
{
    public function __construct(
        private MarkdownSiteImportScanner $scanner,
    ) {
    }

    /**
     * @return list<NormalizedImportRow>
     */
    public function parseDirectory(string $pagesRoot): array
    {
        $pagesRoot = rtrim($pagesRoot, '/\\');
        if (!is_dir($pagesRoot)) {
            throw new FlatFileException('Grav pages directory not found: ' . $pagesRoot);
        }

        $rows = [];
        foreach ($this->scanner->scanDirectory($pagesRoot) as $file) {
            /** @var array<string, mixed> $frontMatter */
            $frontMatter = $file['frontMatter'];
            if ($this->shouldSkip($frontMatter)) {
                continue;
            }

            $relative = $file['relativePath'];
            $dir = str_replace('\\', '/', dirname($relative));
            $segments = $dir === '.' ? [] : explode('/', $dir);
            $slug = MarkdownImportHelpers::slugFromPathSegments($segments, basename($relative));

            $type = $this->resolveType($frontMatter, $relative);
            $title = MarkdownImportHelpers::pickTitle($frontMatter, $slug);

            $rows[] = ContentImportRowFactory::create(
                $type,
                $slug,
                $title,
                $file['body'],
                MarkdownImportHelpers::mapPublishedStatus($frontMatter, 'published'),
                MarkdownImportHelpers::pickDate($frontMatter),
                MarkdownImportHelpers::pickDescription($frontMatter),
                MarkdownImportHelpers::collectTags($frontMatter),
                'grav',
            );
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $frontMatter
     */
    private function shouldSkip(array $frontMatter): bool
    {
        if (array_key_exists('routable', $frontMatter) && $frontMatter['routable'] === false) {
            return true;
        }

        $template = strtolower(trim((string) ($frontMatter['template'] ?? '')));
        if (in_array($template, ['modular', 'external'], true)) {
            return true;
        }

        return false;
    }

    /**
     * @param array<string, mixed> $frontMatter
     */
    private function resolveType(array $frontMatter, string $relativePath): string
    {
        $date = trim((string) ($frontMatter['date'] ?? ''));
        if ($date !== '') {
            return 'article';
        }

        $template = strtolower(trim((string) ($frontMatter['template'] ?? '')));
        if ($template === 'item' || str_contains(strtolower($relativePath), '/blog/')) {
            return 'article';
        }

        return 'page';
    }
}
