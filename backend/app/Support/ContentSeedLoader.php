<?php

declare(strict_types=1);

namespace PaginiumCMS\Support;

use RuntimeException;

/**
 * Loads bundled markdown content seeds (marketing pages, samples).
 */
final class ContentSeedLoader
{
    public static function load(string $relativePath): string
    {
        $path = dirname(__DIR__, 2) . '/resources/content-seeds/' . ltrim($relativePath, '/');
        if (!is_file($path)) {
            throw new RuntimeException('Content seed not found: ' . $relativePath);
        }

        $contents = file_get_contents($path);
        if ($contents === false || trim($contents) === '') {
            throw new RuntimeException('Content seed is empty: ' . $relativePath);
        }

        return $contents;
    }
}
