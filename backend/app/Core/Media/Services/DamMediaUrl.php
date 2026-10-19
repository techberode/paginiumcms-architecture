<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Media\Services;

/**
 * Allow-list DAM / storage media URLs for public expand (It.79 / It.58f-e).
 */
final class DamMediaUrl
{
    public static function sanitize(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (self::isAllowedMediaPath($url)) {
            return $url;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        $path = (string) parse_url($url, PHP_URL_PATH);

        return self::isAllowedMediaPath($path) ? $url : '';
    }

    private static function isAllowedMediaPath(string $url): bool
    {
        if (!str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return false;
        }

        if (preg_match('/[\x00-\x1F\x7F\\\\]/', $url) === 1) {
            return false;
        }

        $path = rawurldecode((string) parse_url($url, PHP_URL_PATH));
        if (preg_match('#(^|/)\.\.(/|$)#', str_replace('\\', '/', $path)) === 1) {
            return false;
        }

        return str_starts_with($url, '/storage/')
            || str_starts_with($url, '/api/media/file/');
    }
}
