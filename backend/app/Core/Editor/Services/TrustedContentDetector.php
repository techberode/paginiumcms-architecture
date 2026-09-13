<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Editor\Services;

/**
 * Detects trusted HTML / external embed blocks in stored Markdown (It.91c).
 */
final class TrustedContentDetector
{
    public function __construct(
        private HtmlSafeShortcode $htmlSafe = new HtmlSafeShortcode(),
        private ExternalEmbedShortcode $embed = new ExternalEmbedShortcode(),
    ) {
    }

    /**
     * @return array{html_safe: int, embed: int}
     */
    public function countBlocks(string $markdown): array
    {
        $htmlSafe = 0;
        if (preg_match_all('/:::' . HtmlSafeShortcode::DIRECTIVE . '\s*\n/', $markdown, $htmlMatches) !== false) {
            $htmlSafe = count($htmlMatches[0]);
        }

        $embed = 0;
        if (preg_match_all('/:::' . ExternalEmbedShortcode::DIRECTIVE . '\b/', $markdown, $embedMatches) !== false) {
            $embed = count($embedMatches[0]);
        }

        return [
            'html_safe' => $htmlSafe,
            'embed' => $embed,
        ];
    }

    public function hasTrustedBlocks(string $markdown): bool
    {
        return $this->htmlSafe->containsBlock($markdown) || $this->embed->containsBlock($markdown);
    }
}
