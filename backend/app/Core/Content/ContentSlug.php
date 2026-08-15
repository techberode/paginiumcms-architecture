<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Content;

/**
 * Normalizes content slugs from titles and storage paths.
 */
final class ContentSlug
{
    public static function slugifyTitle(string $title): string
    {
        $title = trim($title);
        if ($title === '') {
            return '';
        }

        $normalized = $title;
        if (class_exists(\Normalizer::class)) {
            $nfd = \Normalizer::normalize($title, \Normalizer::FORM_D);
            if (is_string($nfd)) {
                $normalized = preg_replace('/\p{M}/u', '', $nfd) ?? $nfd;
            }
        } else {
            $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title);
            if (is_string($converted) && $converted !== '') {
                $normalized = $converted;
            }
        }

        $lower = mb_strtolower($normalized, 'UTF-8');
        $slug = preg_replace('/[^a-z0-9]+/', '-', $lower) ?? '';

        return trim($slug, '-');
    }

    public static function slugFromStoragePath(string $path): string
    {
        $basename = pathinfo($path, PATHINFO_FILENAME);

        return trim($basename);
    }

    public static function resolveSlug(string $currentSlug, string $title, string $path): string
    {
        $current = trim($currentSlug);
        if ($current !== '') {
            return $current;
        }

        $fromPath = self::slugFromStoragePath($path);
        if ($fromPath !== '') {
            return $fromPath;
        }

        $fromTitle = self::slugifyTitle($title);
        if ($fromTitle !== '') {
            return $fromTitle;
        }

        return 'draft-' . bin2hex(random_bytes(4));
    }

    public static function isValidSlug(string $slug): bool
    {
        return $slug !== '' && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) === 1;
    }
}
