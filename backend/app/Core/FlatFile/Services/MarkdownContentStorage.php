<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Services;

use PaginiumCMS\Core\FlatFile\Contracts\ContentStorageInterface;
use PaginiumCMS\Core\FlatFile\Contracts\MarkdownParserInterface;

/**
 * Markdown + YAML front matter driver (predvolený formát).
 */
final class MarkdownContentStorage implements ContentStorageInterface
{
    public function __construct(private MarkdownParserInterface $parser)
    {
    }

    public function format(): string
    {
        return 'md';
    }

    public function extension(): string
    {
        return 'md';
    }

    public function buildPath(string $directory, string $slug): string
    {
        return $directory . '/' . $slug . '.md';
    }

    /**
     * @return array{frontMatter: array<string, mixed>, content: string, html: string}
     */
    public function parse(string $raw): array
    {
        $parsed = $this->parser->parse($raw);

        return [
            'frontMatter' => is_array($parsed['frontMatter'] ?? null) ? $parsed['frontMatter'] : [],
            'content' => (string) ($parsed['content'] ?? ''),
            'html' => (string) ($parsed['html'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $frontMatter
     */
    public function serialize(array $frontMatter, string $content): string
    {
        return $this->parser->serialize($frontMatter, $content);
    }
}
