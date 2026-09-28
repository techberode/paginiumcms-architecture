<?php

declare(strict_types=1);

namespace PaginiumCMS\Support;

/**
 * Allow-list for Google Maps iframe embed URLs (contact page + public widgets).
 */
final class MapEmbedUrlGuard
{
    public static function isAllowed(?string $url): bool
    {
        if ($url === null || trim($url) === '') {
            return false;
        }

        $parsed = parse_url(trim($url));
        if (!is_array($parsed)) {
            return false;
        }

        $scheme = strtolower((string) ($parsed['scheme'] ?? ''));
        $host = strtolower((string) ($parsed['host'] ?? ''));
        $path = (string) ($parsed['path'] ?? '');

        return $scheme === 'https'
            && $host === 'www.google.com'
            && str_starts_with($path, '/maps/embed');
    }

    public static function sanitizeSrc(?string $url): string
    {
        if (!self::isAllowed($url)) {
            return '';
        }

        return htmlspecialchars(trim((string) $url), ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8');
    }
}
