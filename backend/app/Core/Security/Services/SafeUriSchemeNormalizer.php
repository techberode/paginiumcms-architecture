<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security\Services;

/**
 * Aligns URI scheme checks with browser/WHATWG parsing: leading C0 controls and spaces are
 * stripped, TAB/LF/CR are removed before the scheme is interpreted.
 */
final class SafeUriSchemeNormalizer
{
    public static function decodedTrimmed(string $uri): string
    {
        return trim(html_entity_decode($uri, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    public static function schemeProbe(string $decodedTrimmed): string
    {
        return str_replace(["\t", "\n", "\r"], '', ltrim($decodedTrimmed, "\x00..\x20"));
    }
}
