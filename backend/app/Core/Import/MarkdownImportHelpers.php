<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Import;

/**
 * Shared slug/status helpers for flat-file CMS imports.
 */
final class MarkdownImportHelpers
{
    public static function stripOrderingPrefix(string $segment): string
    {
        $segment = trim($segment);
        if (preg_match('/^\d+[._-](.+)$/', $segment, $matches) === 1) {
            return trim($matches[1]);
        }

        return $segment;
    }

    /**
     * @param list<string> $segments
     */
    public static function slugFromPathSegments(array $segments, string $fallbackFile): string
    {
        $parts = [];
        foreach ($segments as $segment) {
            $clean = self::stripOrderingPrefix($segment);
            if ($clean === '' || strtolower($clean) === 'default' || strtolower($clean) === 'index') {
                continue;
            }
            $parts[] = $clean;
        }

        if ($parts === []) {
            $parts[] = pathinfo($fallbackFile, PATHINFO_FILENAME);
        }

        $slug = strtolower(implode('-', $parts));
        $slug = preg_replace('/[^a-z0-9_-]+/', '-', $slug) ?? '';

        return trim($slug, '-');
    }

    /**
     * @param array<string, mixed> $frontMatter
     */
    public static function mapPublishedStatus(array $frontMatter, string $default = 'draft'): string
    {
        if (array_key_exists('published', $frontMatter)) {
            $published = $frontMatter['published'];
            if ($published === false || $published === 0 || $published === '0') {
                return 'draft';
            }
            if ($published === true || $published === 1 || $published === '1') {
                return 'published';
            }
        }

        if (array_key_exists('visible', $frontMatter)) {
            $visible = $frontMatter['visible'];
            if ($visible === false || $visible === 0 || $visible === '0') {
                return 'draft';
            }
        }

        $status = strtolower(trim((string) ($frontMatter['status'] ?? '')));
        if (in_array($status, ['publish', 'published', 'live', 'public'], true)) {
            return 'published';
        }
        if (in_array($status, ['draft', 'unpublished', 'private'], true)) {
            return 'draft';
        }

        return $default;
    }

    /**
     * @param array<string, mixed> $frontMatter
     * @return list<string>
     */
    public static function collectTags(array $frontMatter): array
    {
        $tags = [];
        foreach (['tags', 'tag', 'categories', 'category'] as $key) {
            if (!array_key_exists($key, $frontMatter)) {
                continue;
            }
            $value = $frontMatter[$key];
            if (is_string($value) && trim($value) !== '') {
                $tags[] = trim($value);
                continue;
            }
            if (is_array($value)) {
                foreach ($value as $entry) {
                    if (is_string($entry) && trim($entry) !== '') {
                        $tags[] = trim($entry);
                    }
                }
            }
        }

        return array_values(array_unique($tags));
    }

    /**
     * @param array<string, mixed> $frontMatter
     */
    public static function pickDate(array $frontMatter): string
    {
        foreach (['date', 'publishDate', 'published_at', 'pubdate'] as $key) {
            $value = trim((string) ($frontMatter[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return gmdate('Y-m-d H:i:s');
    }

    /**
     * @param array<string, mixed> $frontMatter
     */
    public static function pickTitle(array $frontMatter, string $fallback): string
    {
        $title = trim((string) ($frontMatter['title'] ?? ''));
        if ($title !== '') {
            return $title;
        }

        return $fallback;
    }

    /**
     * @param array<string, mixed> $frontMatter
     */
    public static function pickDescription(array $frontMatter): string
    {
        foreach (['description', 'summary', 'excerpt', 'subtitle'] as $key) {
            $value = trim(strip_tags((string) ($frontMatter[$key] ?? '')));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}
