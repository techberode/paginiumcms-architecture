<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Services;

/**
 * Finds content-relative media paths referenced in markdown/front matter.
 */
final class ContentMediaReferenceExtractor
{
    private const STORAGE_PREFIX = '/storage/app/content/';

    /**
     * @return list<string> Content-relative paths (e.g. media/foo.jpg), deduplicated.
     */
    public function extractFromText(string $text): array
    {
        if ($text === '') {
            return [];
        }

        $found = [];

        if (preg_match_all('#' . preg_quote(self::STORAGE_PREFIX, '#') . '(media/[^\s"\')\]>]+)#i', $text, $matches)) {
            foreach ($matches[1] as $path) {
                $found[$this->normalizeRelativePath($path)] = true;
            }
        }

        if (preg_match_all('#\((/storage/app/content/(media/[^)\s]+))\)#i', $text, $matches)) {
            foreach ($matches[2] as $path) {
                $found[$this->normalizeRelativePath($path)] = true;
            }
        }

        if (preg_match_all('#(?:^|[\s"\'])(media/[a-zA-Z0-9_./-]+)#', $text, $matches)) {
            foreach ($matches[1] as $path) {
                if (str_contains($path, '..')) {
                    continue;
                }
                $found[$this->normalizeRelativePath($path)] = true;
            }
        }

        return array_keys($found);
    }

    /**
     * @param array<int|string, mixed> $frontMatter
     * @return list<string>
     */
    public function extractFromFrontMatter(array $frontMatter): array
    {
        $paths = [];
        foreach ($frontMatter as $value) {
            if (is_string($value)) {
                foreach ($this->extractFromText($value) as $path) {
                    $paths[$path] = true;
                }
            }
        }

        return array_keys($paths);
    }

    public function toContentRelativePath(string $reference): ?string
    {
        $reference = trim($reference);
        if ($reference === '') {
            return null;
        }

        if (str_starts_with($reference, self::STORAGE_PREFIX)) {
            return $this->normalizeRelativePath(substr($reference, strlen(self::STORAGE_PREFIX)));
        }

        if (str_starts_with($reference, 'media/')) {
            return $this->normalizeRelativePath($reference);
        }

        return null;
    }

    private function normalizeRelativePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#/+#', '/', $path) ?? $path;

        return ltrim($path, '/');
    }
}
