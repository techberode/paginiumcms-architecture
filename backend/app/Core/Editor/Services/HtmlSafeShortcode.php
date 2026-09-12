<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Editor\Services;

/**
 * Expands guarded :::html-safe Markdown blocks (It.91).
 * Body is purified on save; expand outputs wrapped fragment for CommonMark.
 */
final class HtmlSafeShortcode
{
    public const DIRECTIVE = 'html-safe';

    public function expand(string $markdown): string
    {
        $expanded = preg_replace_callback(
            '/:::html-safe\s*\n([\s\S]*?)\n\s*:::/',
            function (array $matches): string {
                $inner = trim((string) $matches[1]);
                if ($inner === '') {
                    return '';
                }

                return '<div class="paginium-html-safe">' . $inner . '</div>';
            },
            $markdown
        );

        return is_string($expanded) ? $expanded : $markdown;
    }

    /**
     * @return list<array{start: int, end: int, body: string}>
     */
    public function extractBlocks(string $markdown): array
    {
        $blocks = [];
        if (preg_match_all('/:::html-safe\s*\n([\s\S]*?)\n\s*:::/', $markdown, $matches, PREG_OFFSET_CAPTURE) !== false) {
            foreach ($matches[0] as $index => $fullMatch) {
                $blocks[] = [
                    'start' => (int) $fullMatch[1],
                    'end' => (int) $fullMatch[1] + strlen((string) $fullMatch[0]),
                    'body' => trim((string) $matches[1][$index][0]),
                ];
            }
        }

        return $blocks;
    }

    public function containsBlock(string $markdown): bool
    {
        return str_contains($markdown, ':::html-safe');
    }

    public function replaceBlockBody(string $markdown, int $blockIndex, string $purifiedBody): string
    {
        $blocks = $this->extractBlocks($markdown);
        if (!isset($blocks[$blockIndex])) {
            return $markdown;
        }

        $block = $blocks[$blockIndex];
        $replacement = ":::html-safe\n" . $purifiedBody . "\n:::";

        return substr($markdown, 0, $block['start'])
            . $replacement
            . substr($markdown, $block['end']);
    }

    public function normalizeAllBodies(string $markdown, callable $purify): string
    {
        $result = $markdown;
        $blocks = $this->extractBlocks($markdown);

        for ($index = count($blocks) - 1; $index >= 0; $index--) {
            $block = $blocks[$index];
            $pure = $purify($block['body']);
            $replacement = ":::html-safe\n" . $pure . "\n:::";
            $result = substr($result, 0, $block['start'])
                . $replacement
                . substr($result, $block['end']);
        }

        return $result;
    }

    public function stripBlocks(string $markdown): string
    {
        $stripped = preg_replace('/:::html-safe\s*\n[\s\S]*?\n\s*:::/', '', $markdown);

        return is_string($stripped) ? $stripped : $markdown;
    }
}
