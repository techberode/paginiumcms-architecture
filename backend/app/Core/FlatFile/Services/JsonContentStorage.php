<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Services;

use PaginiumCMS\Core\FlatFile\Contracts\ContentStorageInterface;
use PaginiumCMS\Core\FlatFile\Contracts\MarkdownContentParserInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Support\JsonHelper;

/**
 * Čistý JSON driver pre obsah (metadata + body v jednom súbore).
 *
 * Formát súboru:
 * {
 *   "title": "...",
 *   "slug": "...",
 *   "status": "draft|published|archived",
 *   "author": "...",
 *   "content": "# Markdown body",
 *   "template": "...",
 *   "featuredImage": "...",
 *   "tags": [],
 *   "createdAt": "ISO8601",
 *   "updatedAt": "ISO8601"
 * }
 */
final class JsonContentStorage implements ContentStorageInterface
{
    /** @var list<string> */
    private array $reservedKeys = ['content', 'html'];

    public function __construct(private MarkdownContentParserInterface $contentParser)
    {
    }

    public function format(): string
    {
        return 'json';
    }

    public function extension(): string
    {
        return 'json';
    }

    public function buildPath(string $directory, string $slug): string
    {
        return $directory . '/' . $slug . '.json';
    }

    /**
     * @return array{frontMatter: array<string, mixed>, content: string, html: string}
     */
    public function parse(string $raw): array
    {
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new FlatFileException('Neplatný JSON obsah');
        }

        $content = (string) ($decoded['content'] ?? '');
        unset($decoded['content'], $decoded['html']);

        return [
            'frontMatter' => $decoded,
            'content' => $content,
            'html' => $this->contentParser->parse($content),
        ];
    }

    /**
     * @param array<string, mixed> $frontMatter
     */
    public function serialize(array $frontMatter, string $content): string
    {
        $payload = $frontMatter;
        foreach ($this->reservedKeys as $key) {
            unset($payload[$key]);
        }
        $payload['content'] = $content;

        return JsonHelper::encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
