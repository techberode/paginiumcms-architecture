<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Drafts\Services;

use PaginiumCMS\Core\Drafts\Contracts\DraftManagerInterface;
use PaginiumCMS\Core\Drafts\Models\Draft;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;

/**
 * === Služba: DraftManager ===
 * Flat-file správa konceptov. Každý koncept je samostatný JSON súbor
 * `{basePath}/{type}/{slug}.json` (predvolene `data/drafts/{type}/{slug}.json`).
 *
 * Zámerne používa existujúce `FileReader`/`FileWriter` (jednotný I/O, path-traversal ochrana),
 * takže integrácia do Jadra je bezproblémová a bez duplicity logiky.
 */
final class DraftManager implements DraftManagerInterface
{
    private const ALLOWED_TYPES = ['page', 'article'];

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private ContentRepositoryInterface $content,
        private string $basePath = 'data/drafts'
    ) {
        $this->basePath = trim($basePath, '/');
    }

    /**
     * @param array<int|string, mixed> $payload
     */
    public function save(string $type, string $slug, array $payload, string $userId): Draft
    {
        $snapshot = $payload['editorSnapshot'] ?? null;
        $editorSnapshot = is_array($snapshot) && $snapshot !== [] ? $snapshot : null;

        $draft = new Draft(
            $this->normalizeType($type),
            $slug,
            (string) ($payload['title'] ?? ''),
            (string) ($payload['content'] ?? ''),
            (string) ($payload['status'] ?? 'draft'),
            (string) ($payload['baseRevision'] ?? ''),
            $userId,
            time(),
            $editorSnapshot,
            (bool) ($payload['unsavedNew'] ?? false)
        );

        // createBackup=false: koncepty sa prepisujú často (každých 60 s), zálohy netreba.
        $this->writer->write(
            $this->pathFor($type, $slug),
            (string) json_encode($draft->jsonSerialize(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            false
        );

        return $draft;
    }

    public function get(string $type, string $slug): ?Draft
    {
        $path = $this->pathFor($type, $slug);

        if (!$this->reader->exists($path)) {
            return null;
        }

        try {
            $decoded = json_decode($this->reader->read($path), true);
        } catch (FlatFileException) {
            return null;
        }

        return is_array($decoded) ? Draft::fromArray($decoded) : null;
    }

    public function exists(string $type, string $slug): bool
    {
        return $this->reader->exists($this->pathFor($type, $slug));
    }

    public function discard(string $type, string $slug): void
    {
        $path = $this->pathFor($type, $slug);

        if ($this->reader->exists($path)) {
            // moveToTrash=false: koncept je dočasný, netreba ho archivovať do koša.
            $this->writer->delete($path, false);
        }
    }

    public function listOrphansForUser(string $userId, ?string $type = null): array
    {
        $userId = trim($userId);
        if ($userId === '') {
            return [];
        }

        $types = self::ALLOWED_TYPES;
        if ($type !== null && in_array($type, self::ALLOWED_TYPES, true)) {
            $types = [$type];
        }

        $items = [];
        foreach ($types as $contentType) {
            $directory = $this->basePath . '/' . $contentType;

            try {
                $files = $this->reader->listFiles($directory, '*.json');
            } catch (FlatFileException) {
                continue;
            }

            foreach ($files as $relativeFile) {
                $draft = $this->decodeDraftFile($contentType, (string) $relativeFile);
                if ($draft === null) {
                    continue;
                }

                if ($draft->getSavedBy() !== $userId || !$draft->isUnsavedNew()) {
                    continue;
                }

                if ($this->content->findBySlug($draft->getSlug(), $contentType) !== null) {
                    continue;
                }

                $items[] = $draft;
            }
        }

        usort($items, static fn (Draft $a, Draft $b): int => $b->getSavedAt() <=> $a->getSavedAt());

        return $items;
    }

    private function decodeDraftFile(string $type, string $relativePath): ?Draft
    {
        $basename = basename($relativePath, '.json');
        if ($basename === '') {
            return null;
        }

        try {
            $decoded = json_decode($this->reader->read($relativePath), true);
        } catch (FlatFileException) {
            return null;
        }

        if (!is_array($decoded)) {
            return null;
        }

        $decoded['type'] = $type;
        if (!isset($decoded['slug']) || (string) $decoded['slug'] === '') {
            $decoded['slug'] = $basename;
        }

        return Draft::fromArray($decoded);
    }

    /**
     * Bezpečne zloží relatívnu cestu ku konceptu.
     */
    private function pathFor(string $type, string $slug): string
    {
        $type = $this->normalizeType($type);
        $safeSlug = $this->sanitizeSlug($slug);

        return $this->basePath . '/' . $type . '/' . $safeSlug . '.json';
    }

    private function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));

        return in_array($type, self::ALLOWED_TYPES, true) ? $type : 'page';
    }

    /**
     * Očistí slug na bezpečný názov súboru (žiadny path traversal, len povolené znaky).
     */
    private function sanitizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9._-]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-._');

        return $slug !== '' ? $slug : 'untitled';
    }
}
