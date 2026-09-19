<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Translation\Services;

/**
 * Protects Markdown/HTML structure tokens before sending text to a provider (It.76).
 */
final class TranslationPlaceholderGuard
{
    /**
     * @return array{text: string, tokens: array<string, string>}
     */
    public function protect(string $text): array
    {
        $tokens = [];
        $index = 0;
        $patterns = [
            '/```[\s\S]*?```/u',
            '/`[^`]+`/u',
            '/https?:\/\/[^\s<>"\']+/u',
            '/\[[a-z0-9_-]+(?:\s[^\]]*)?\]/iu',
            '/media_[a-zA-Z0-9_-]+/u',
        ];

        $protected = $text;
        foreach ($patterns as $pattern) {
            $protected = preg_replace_callback(
                $pattern,
                static function (array $match) use (&$tokens, &$index): string {
                    $key = '⟦T' . $index . '⟧';
                    $tokens[$key] = $match[0];
                    $index++;

                    return $key;
                },
                $protected
            ) ?? $protected;
        }

        return ['text' => $protected, 'tokens' => $tokens];
    }

    /**
     * @param array<string, string> $tokens
     */
    public function restore(string $text, array $tokens): string
    {
        return strtr($text, $tokens);
    }
}
