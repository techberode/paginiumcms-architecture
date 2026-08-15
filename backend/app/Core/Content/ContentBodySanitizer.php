<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Content;

/**
 * Detects and removes front-matter/metadata accidentally embedded in markdown body text.
 */
final class ContentBodySanitizer
{
    public static function looksLikeMetadataLeak(string $body): bool
    {
        if (trim($body) === '') {
            return false;
        }

        return preg_match('/\Rseo:\R[ \t]*\R[ \t]+title:/', $body) === 1
            || str_contains($body, "localeStatus:")
            || str_contains($body, 'seoTitle:')
            || preg_match('/\Rslug:[ \t]+\S+[ \t]+title:/', $body) === 1
            || preg_match('/\RschemaVersion:[ \t]+[0-9]/', $body) === 1;
    }

    public static function stripEmbeddedMetadataLeak(string $body): string
    {
        if (!self::looksLikeMetadataLeak($body)) {
            return $body;
        }

        $patterns = [
            '/\Rseo:\R[ \t]*\R[ \t]+title:/',
            '/\RlocaleStatus:/',
            '/\Rslug:[ \t]+\S+[ \t]+title:/',
            '/\RupdatedAt:[ \t]+[\'"]/',
            '/\RschemaVersion:[ \t]+[0-9]/',
        ];

        $cutAt = null;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $body, $match, PREG_OFFSET_CAPTURE)) {
                $pos = (int) $match[0][1];
                if ($pos > 0 && ($cutAt === null || $pos < $cutAt)) {
                    $cutAt = $pos;
                }
            }
        }

        if ($cutAt === null) {
            return $body;
        }

        return rtrim(substr($body, 0, $cutAt));
    }
}
