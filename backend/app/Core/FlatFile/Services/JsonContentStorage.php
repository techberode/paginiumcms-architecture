<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Services;

use PaginiumCMS\Core\FlatFile\Contracts\ContentStorageInterface;
use PaginiumCMS\Core\Editor\Services\ContentBodyRenderer;
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

    public function __construct(private ContentBodyRenderer $bodyRenderer)
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
        $cachedHtml = isset($decoded['html']) ? (string) $decoded['html'] : null;
        unset($decoded['content'], $decoded['html']);
        $contentFormat = $this->bodyRenderer->normalizeContentFormat(
            $decoded['contentFormat'] ?? null,
            $content
        );

        return [
            'frontMatter' => $decoded,
            'content' => $content,
            'html' => $this->bodyRenderer->resolveHtml($content, $contentFormat, $cachedHtml),
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
        $contentFormat = $this->bodyRenderer->normalizeContentFormat(
            $frontMatter['contentFormat'] ?? null,
            $content
        );
        $payload['content'] = $content;
        $payload['html'] = $this->bodyRenderer->resolveHtml($content, $contentFormat);

        return JsonHelper::encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
