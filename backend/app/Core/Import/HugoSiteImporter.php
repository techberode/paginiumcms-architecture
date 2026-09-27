<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Import;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;

/**
 * Imports Hugo content tree (content/posts → articles, other sections → pages).
 *
 * @phpstan-import-type NormalizedImportRow from ImportRowTypes
 */
final class HugoSiteImporter
{
    public function __construct(
        private MarkdownSiteImportScanner $scanner,
    ) {
    }

    /**
     * @return list<NormalizedImportRow>
     */
    public function parseDirectory(string $contentRoot): array
    {
        $contentRoot = rtrim($contentRoot, '/\\');
        if (!is_dir($contentRoot)) {
            throw new FlatFileException('Hugo content directory not found: ' . $contentRoot);
        }

        $rows = [];
        foreach ($this->scanner->scanDirectory($contentRoot) as $file) {
            /** @var array<string, mixed> $frontMatter */
            $frontMatter = $file['frontMatter'];
            $relative = str_replace('\\', '/', $file['relativePath']);

            if (str_starts_with($relative, 'archetypes/')) {
                continue;
            }

            $isPost = str_starts_with($relative, 'posts/')
                || str_contains($relative, '/posts/')
                || strtolower((string) ($frontMatter['type'] ?? '')) === 'post';

            $type = $isPost ? 'article' : 'page';
            $dir = dirname($relative);
            $segments = $dir === '.' ? [] : explode('/', $dir);
            $slug = MarkdownImportHelpers::slugFromPathSegments($segments, basename($relative));

            if ($slug === '') {
                continue;
            }

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
                'hugo',
            );
        }

        return $rows;
    }
}
