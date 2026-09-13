<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Editor\Services;

/**
 * Strips dangerous constructs from server-rendered Mermaid SVG (It.90c).
 */
final class MermaidSvgSanitizer
{
    public function sanitize(string $svg): string
    {
        $svg = trim($svg);
        if ($svg === '' || !str_contains(strtolower($svg), '<svg')) {
            return '';
        }

        $lower = strtolower($svg);
        if (
            str_contains($lower, '<script')
            || str_contains($lower, '<foreignobject')
            || str_contains($lower, 'javascript:')
            || str_contains($lower, 'data:text/html')
        ) {
            return '';
        }

        $svg = preg_replace('/\s+on[a-z]+\s*=\s*(["\']).*?\1/i', '', $svg) ?? $svg;
        $svg = preg_replace('/<\s*(script|foreignobject|iframe|object|embed|link|meta)\b[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $svg) ?? $svg;
        $svg = preg_replace('/<\s*(script|foreignobject|iframe|object|embed|link|meta)\b[^>]*\/?>/i', '', $svg) ?? $svg;

        return trim($svg);
    }
}
