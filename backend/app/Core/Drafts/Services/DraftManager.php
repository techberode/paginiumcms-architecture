<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Drafts\Services;

use PaginiumCMS\Core\Drafts\Contracts\DraftManagerInterface;
use PaginiumCMS\Core\Drafts\Models\Draft;
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
        private string $basePath = 'data/drafts'
    ) {
        $this->basePath = trim($basePath, '/');
    }

    /**
     * @param array<int|string, mixed> $payload
     */
    public function save(string $type, string $slug, array $payload, string $userId): Draft
    {
        $draft = new Draft(
            $this->normalizeType($type),
            $slug,
            (string) ($payload['title'] ?? ''),
            (string) ($payload['content'] ?? ''),
            (string) ($payload['status'] ?? 'draft'),
            (string) ($payload['baseRevision'] ?? ''),
            $userId,
            time()
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
