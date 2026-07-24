<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Services;

use PaginiumCMS\Core\FlatFile\Contracts\MarkdownContentParserInterface;

/**
 * Deterministic tag & meta description suggestions from title + body (It.57).
 */
final class ContentMetaGenerator
{
    /** @var list<string> */
    private const STOPWORDS = [
        // SK
        'a', 'aby', 'aj', 'ak', 'ako', 'ale', 'alebo', 'ani', 'áno', 'asi', 'bez', 'bol', 'bola', 'boli', 'bolo',
        'bude', 'budem', 'budeš', 'budeme', 'budete', 'budú', 'by', 'byť', 'cez', 'co', 'čo', 'či', 'čo', 'dnes',
        'do', 'ho', 'i', 'iba', 'ich', 'iné', 'je', 'jeho', 'jej', 'jemu', 'ju', 'k', 'kam', 'každý', 'kde', 'keď',
        'kto', 'ku', 'lebo', 'len', 'ma', 'má', 'mať', 'medzi', 'mi', 'mne', 'mnou', 'môj', 'môže', 'my', 'na',
        'nad', 'nám', 'nás', 'ne', 'než', 'nie', 'no', 'o', 'od', 'on', 'ona', 'oni', 'ono', 'po', 'pod', 'pre',
        'pred', 'pri', 's', 'sa', 'si', 'so', 'som', 'ste', 'sú', 'ta', 'tá', 'tam', 'te', 'teda', 'ten', 'tento',
        'ti', 'tie', 'to', 'toho', 'tom', 'tomu', 'tu', 'tvoj', 'ty', 'u', 'už', 'v', 'vo', 'vy', 'vám', 'vás',
        'veľmi', 'viac', 'vo', 'však', 'všetko', 'z', 'za', 'zo', 'že',
        // EN
        'the', 'and', 'for', 'are', 'but', 'not', 'you', 'all', 'can', 'had', 'her', 'was', 'one', 'our', 'out',
        'day', 'get', 'has', 'him', 'his', 'how', 'its', 'may', 'new', 'now', 'old', 'see', 'two', 'way', 'who',
        'boy', 'did', 'she', 'use', 'her', 'many', 'some', 'time', 'very', 'when', 'come', 'here', 'just', 'like',
        'long', 'make', 'over', 'such', 'take', 'than', 'them', 'well', 'were', 'what', 'with', 'your', 'from',
        'this', 'that', 'have', 'will', 'into', 'about', 'after', 'also', 'been', 'being', 'could', 'each',
        'more', 'most', 'other', 'should', 'their', 'there', 'these', 'they', 'those', 'through', 'under', 'where',
        'which', 'while', 'would', 'without',
    ];

    public function __construct(
        private MarkdownContentParserInterface $markdownParser,
    ) {
    }

    /**
     * @param list<string> $existingTags
     *
     * @return list<string>
     */
    public function suggestTags(
        string $title,
        string $body,
        string $bodyFormat,
        int $maxTags,
        array $existingTags = [],
    ): array {
        $maxTags = max(1, min(20, $maxTags));
        $plain = $this->extractPlainText($body, $bodyFormat);
        $combined = mb_strtolower($title . ' ' . $plain);
        $titleTokens = $this->tokenize(mb_strtolower($title));
        $tokens = $this->tokenize($combined);

        $freq = [];
        foreach ($tokens as $token) {
            if (!$this->isCandidateToken($token)) {
                continue;
            }
            $freq[$token] = ($freq[$token] ?? 0) + 1;
        }

        foreach ($titleTokens as $token) {
            if (!$this->isCandidateToken($token)) {
                continue;
            }
            $freq[$token] = ($freq[$token] ?? 0) + 2;
        }

        arsort($freq);

        $exclude = [];
        foreach ($existingTags as $tag) {
            $exclude[mb_strtolower(trim($tag))] = true;
        }

        $tags = [];
        foreach (array_keys($freq) as $token) {
            if (isset($exclude[$token])) {
                continue;
            }
            $tags[] = $this->formatTag($token);
            if (count($tags) >= $maxTags) {
                break;
            }
        }

        return $tags;
    }

    public function suggestDescription(
        string $title,
        string $body,
        string $bodyFormat,
        int $maxLength,
    ): string {
        $maxLength = max(80, min(320, $maxLength));
        $plain = trim($this->extractPlainText($body, $bodyFormat));
        if ($plain === '') {
            return mb_substr(trim($title), 0, $maxLength);
        }

        $text = preg_replace('/\s+/u', ' ', $plain) ?? $plain;
        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }

        $chunk = mb_substr($text, 0, $maxLength);
        if (preg_match('/^(.*[.!?…])\s/u', $chunk, $matches) === 1) {
            return trim($matches[1]);
        }

        $lastSpace = mb_strrpos($chunk, ' ');
        if ($lastSpace !== false && $lastSpace > (int) ($maxLength * 0.5)) {
            return rtrim(mb_substr($chunk, 0, $lastSpace), '.,;:-') . '…';
        }

        return rtrim($chunk, '.,;:- ') . '…';
    }

    public function extractPlainText(string $body, string $bodyFormat): string
    {
        return match ($bodyFormat) {
            'html' => trim(html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            'tiptap_json' => $this->extractTiptapPlainText($body),
            default => $this->markdownParser->stripMarkdown($body),
        };
    }

    private function extractTiptapPlainText(string $json): string
    {
        if (trim($json) === '') {
            return '';
        }

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return '';
        }

        if (!is_array($decoded)) {
            return '';
        }

        $parts = [];
        $this->collectTiptapText($decoded, $parts);

        return trim(preg_replace('/\s+/u', ' ', implode(' ', $parts)) ?? '');
    }

    /**
     * @param array<string, mixed> $node
     * @param list<string> $parts
     */
    private function collectTiptapText(array $node, array &$parts): void
    {
        if (isset($node['text']) && is_string($node['text'])) {
            $parts[] = $node['text'];
        }

        if (!isset($node['content']) || !is_array($node['content'])) {
            return;
        }

        foreach ($node['content'] as $child) {
            if (is_array($child)) {
                $this->collectTiptapText($child, $parts);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function tokenize(string $text): array
    {
        $normalized = mb_strtolower($text);
        $normalized = preg_replace('/[^\p{L}\p{N}\-]+/u', ' ', $normalized) ?? $normalized;
        $parts = preg_split('/\s+/u', trim($normalized), -1, PREG_SPLIT_NO_EMPTY);

        return is_array($parts) ? $parts : [];
    }

    private function isCandidateToken(string $token): bool
    {
        if (mb_strlen($token) < 3) {
            return false;
        }

        if (in_array($token, self::STOPWORDS, true)) {
            return false;
        }

        if (preg_match('/^\d+$/', $token) === 1) {
            return false;
        }

        return true;
    }

    private function formatTag(string $token): string
    {
        if (str_contains($token, '-')) {
            return $token;
        }

        return $token;
    }
}
