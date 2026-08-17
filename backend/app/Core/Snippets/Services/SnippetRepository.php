<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Snippets\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\Security\Services\ContentSecuritySanitizer;
use PaginiumCMS\Core\Snippets\Models\SnippetRecord;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * CRUD for reusable snippet bodies at data/snippets/{name}.json (It.81f).
 */
final class SnippetRepository
{
    public function __construct(
        private SnippetRegistry $registry,
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private ContentSecuritySanitizer $sanitizer,
        private string $snippetsRelativeDir = 'data/snippets',
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        $items = [];
        foreach ($this->registry->all() as $name => $record) {
            $items[] = $this->buildListItem($name, $record);
        }

        usort($items, static fn (array $a, array $b): int => strcmp((string) $a['name'], (string) $b['name']));

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $name): array
    {
        $name = $this->normalizeName($name);
        $record = $this->registry->get($name);
        if ($record === null) {
            throw new RuntimeException('Snippet not found: ' . $name);
        }

        return [
            'record' => $record->toArray(),
            'snippet' => $this->readSnippetFile($name),
        ];
    }

    /**
     * Body for shortcode expansion — empty string when missing or disabled.
     */
    public function resolveBody(string $name): string
    {
        $name = $this->normalizeName($name);
        $record = $this->registry->get($name);
        if ($record === null || !$record->enabled) {
            return '';
        }

        try {
            $snippet = $this->readSnippetFile($name);
        } catch (RuntimeException) {
            return '';
        }

        $body = (string) ($snippet['body'] ?? '');
        $format = (string) ($snippet['format'] ?? 'markdown');

        if ($format === 'html') {
            return $this->sanitizer->sanitizeHtml($body);
        }

        return $body;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function save(string $name, array $payload): array
    {
        $name = $this->normalizeName($name);
        $normalized = $this->normalizePayload($name, $payload);

        $this->writer->createDirectory($this->snippetsRelativeDir);
        $relativePath = $this->snippetRelativePath($name);
        $canonical = JsonHelper::encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $this->writer->write($relativePath, $canonical, false);

        $record = new SnippetRecord(
            $name,
            (string) $normalized['title'],
            (bool) $normalized['enabled'],
            (int) $normalized['version'],
            (string) $normalized['updatedAt'],
        );
        $this->registry->upsert($record);

        return $this->buildListItem($name, $record);
    }

    public function delete(string $name): void
    {
        $name = $this->normalizeName($name);
        if ($this->registry->get($name) === null) {
            throw new RuntimeException('Snippet not found: ' . $name);
        }

        $relativePath = $this->snippetRelativePath($name);
        if ($this->reader->exists($relativePath)) {
            $this->writer->delete($relativePath, false);
        }

        $this->registry->remove($name);
    }

    /**
     * @return array<string, mixed>
     */
    private function readSnippetFile(string $name): array
    {
        $relativePath = $this->snippetRelativePath($name);
        if (!$this->reader->exists($relativePath)) {
            throw new RuntimeException('Snippet file missing: ' . $name);
        }

        /** @var array<string, mixed> $decoded */
        $decoded = JsonHelper::decode($this->reader->read($relativePath));

        return $decoded;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalizePayload(string $name, array $payload): array
    {
        $title = trim((string) ($payload['title'] ?? $name));
        if ($title === '') {
            $title = $name;
        }

        $body = (string) ($payload['body'] ?? '');
        $format = (string) ($payload['format'] ?? 'markdown');
        if (!in_array($format, ['markdown', 'html'], true)) {
            $format = 'markdown';
        }

        if ($format === 'html') {
            $body = $this->sanitizer->sanitizeHtml($body);
        }

        if (strlen($body) > 65536) {
            throw new RuntimeException('Snippet body exceeds maximum length.');
        }

        return [
            'name' => $name,
            'title' => $title,
            'body' => $body,
            'format' => $format,
            'version' => max(1, (int) ($payload['version'] ?? 1)),
            'enabled' => (bool) ($payload['enabled'] ?? true),
            'updatedAt' => gmdate('c'),
        ];
    }

    private function snippetRelativePath(string $name): string
    {
        return $this->snippetsRelativeDir . '/' . $name . '.json';
    }

    /**
     * @return array<string, mixed>
     */
    private function buildListItem(string $name, SnippetRecord $record): array
    {
        return [
            'name' => $name,
            'title' => $record->title,
            'enabled' => $record->enabled,
            'version' => $record->version,
            'updatedAt' => $record->updatedAt,
        ];
    }

    private function normalizeName(string $name): string
    {
        $name = trim($name);
        if ($name === '' || !preg_match('/^[a-z][a-z0-9_-]{0,39}$/', $name)) {
            throw new RuntimeException('Invalid snippet name.');
        }

        return $name;
    }
}
