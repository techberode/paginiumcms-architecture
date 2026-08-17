<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Snippets\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Exception\FileNotFoundException;

/**
 * Finds content files referencing a snippet tag (It.81f cache invalidation).
 */
final class SnippetReferenceScanner
{
    public function __construct(
        private FileReaderInterface $reader,
    ) {
    }

    /**
     * @return list<array{type: string, slug: string}>
     */
    public function findReferences(string $snippetName): array
    {
        $snippetName = trim($snippetName);
        if ($snippetName === '') {
            return [];
        }

        $needle = 'name="' . $snippetName . '"';
        $needleAlt = "name='" . $snippetName . "'";
        $matches = [];

        foreach (['content/pages', 'content/blog'] as $directory) {
            foreach ($this->listContentFiles($directory) as $relativePath) {
                try {
                    $raw = $this->reader->read($relativePath);
                } catch (\Throwable) {
                    continue;
                }

                if (!str_contains($raw, '[snippet') || (!str_contains($raw, $needle) && !str_contains($raw, $needleAlt))) {
                    continue;
                }

                $slug = $this->slugFromPath($relativePath);
                if ($slug === '') {
                    continue;
                }

                $matches[] = [
                    'type' => str_contains($directory, 'blog') ? 'article' : 'page',
                    'slug' => $slug,
                ];
            }
        }

        return $matches;
    }

    /**
     * @return list<string>
     */
    private function listContentFiles(string $directory): array
    {
        try {
            return array_merge(
                $this->reader->listFiles($directory, '*.md'),
                $this->reader->listFiles($directory, '*.json')
            );
        } catch (FileNotFoundException) {
            return [];
        }
    }

    private function slugFromPath(string $relativePath): string
    {
        $basename = basename($relativePath);
        $slug = preg_replace('/\.(md|json)$/i', '', $basename);

        return is_string($slug) ? $slug : '';
    }
}
