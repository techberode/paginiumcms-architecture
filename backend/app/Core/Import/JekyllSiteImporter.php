<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Import;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;

/**
 * Imports Jekyll posts/pages from a site root (_posts, _pages, or root .md).
 *
 * @phpstan-import-type NormalizedImportRow from ImportRowTypes
 */
final class JekyllSiteImporter
{
    public function __construct(
        private MarkdownSiteImportScanner $scanner,
    ) {
    }

    /**
     * @return list<NormalizedImportRow>
     */
    public function parseDirectory(string $siteRoot): array
    {
        $siteRoot = rtrim($siteRoot, '/\\');
        if (!is_dir($siteRoot)) {
            throw new FlatFileException('Jekyll site directory not found: ' . $siteRoot);
        }

        $rows = [];
        foreach ($this->scanner->scanDirectory($siteRoot) as $file) {
            /** @var array<string, mixed> $frontMatter */
            $frontMatter = $file['frontMatter'];
            $relative = str_replace('\\', '/', $file['relativePath']);

            if (str_starts_with($relative, '_drafts/') || str_contains($relative, '/_drafts/')) {
                continue;
            }

            $isPost = str_starts_with($relative, '_posts/') || str_contains($relative, '/_posts/');
            $type = $isPost ? 'article' : 'page';

            if ($isPost) {
                $slug = $this->slugFromJekyllPost(basename($relative));
            } else {
                $dir = dirname($relative);
                $segments = $dir === '.' ? [] : explode('/', $dir);
                $slug = MarkdownImportHelpers::slugFromPathSegments($segments, basename($relative));
            }

            if ($slug === '') {
                continue;
            }

            $title = MarkdownImportHelpers::pickTitle($frontMatter, $slug);
            $defaultStatus = $isPost ? 'published' : MarkdownImportHelpers::mapPublishedStatus($frontMatter, 'draft');

            $rows[] = ContentImportRowFactory::create(
                $type,
                $slug,
                $title,
                $file['body'],
                MarkdownImportHelpers::mapPublishedStatus($frontMatter, $defaultStatus),
                MarkdownImportHelpers::pickDate($frontMatter),
                MarkdownImportHelpers::pickDescription($frontMatter),
                MarkdownImportHelpers::collectTags($frontMatter),
                'jekyll',
            );
        }

        return $rows;
    }

    private function slugFromJekyllPost(string $filename): string
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        if (preg_match('/^\d{4}-\d{2}-\d{2}-(.+)$/', $name, $matches) === 1) {
            return trim($matches[1], '-');
        }

        return trim($name, '-');
    }
}
