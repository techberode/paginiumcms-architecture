<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Translation\Services;

/**
 * Protects Markdown/HTML structure tokens before sending text to a provider (It.76 / It.102).
 */
final class TranslationPlaceholderGuard
{
    /**
     * @return array{text: string, tokens: array<string, string>}
     */
    public function protect(string $text, bool $protectPairedShortcodeBlocks = false): array
    {
        $tokens = [];
        $index = 0;
        $protected = $text;

        /** @var list<string> $patterns */
        $patterns = [
            '/```[\s\S]*?```/u',
            '/`[^`]+`/u',
            '/https?:\/\/[^\s<>"\']+/u',
        ];

        if ($protectPairedShortcodeBlocks) {
            $patterns[] = '/\[([a-z][a-z0-9_-]*)(?:\s[^\]]*)?\](.*?)\[\/\1\]/ius';
        }

        $patterns = array_merge($patterns, [
            '/\[[a-z0-9_-]+(?:\s[^\]]*)?\]/iu',
            '/\[\/[a-z0-9_-]+\]/iu',
            '/media_[a-zA-Z0-9_-]+/u',
        ]);

        foreach ($patterns as $pattern) {
            $protected = $this->applyPattern($protected, $pattern, $tokens, $index);
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

    /**
     * @param array<string, string> $tokens
     */
    private function applyPattern(string $text, string $pattern, array &$tokens, int &$index): string
    {
        return preg_replace_callback(
            $pattern,
            function (array $match) use (&$tokens, &$index): string {
                $key = '⟦T' . $index . '⟧';
                $tokens[$key] = $match[0];
                $index++;

                return $key;
            },
            $text
        ) ?? $text;
    }
}
