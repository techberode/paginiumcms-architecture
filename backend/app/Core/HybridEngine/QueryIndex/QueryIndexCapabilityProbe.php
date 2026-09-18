<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\HybridEngine\QueryIndex;

use PaginiumCMS\Core\FlatFile\Models\ContentIndexEntry;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PDO;

/**
 * Capability and activation probes for optional SQLite query index (It.92c).
 */
final class QueryIndexCapabilityProbe
{
    public function __construct(
        private QueryIndexPaths $paths,
        private QueryIndexRebuilder $rebuilder,
        private QueryIndexSqliteStore $store,
        private ContentIndexService $contentIndex
    ) {
    }

    /**
     * @param array<string, mixed> $engineSettings
     * @return array<string, mixed>
     */
    public function probe(array $engineSettings): array
    {
        $configured = (string) ($engineSettings['queryIndexDriver'] ?? QueryIndexInterface::DRIVER_JSON);
        $active = $this->activeDriver($configured);
        $pdoOk = extension_loaded('pdo_sqlite');
        $dirWritable = $this->indexDirectoryWritable();
        $filePresent = is_readable($this->paths->absolutePath());
        $integrityOk = $filePresent && $this->store->integrityOk();

        return [
            'queryIndexDriver' => [
                'configured' => $configured,
                'active' => $active,
                'status' => $configured === $active ? 'active' : 'fallback',
            ],
            'capabilities' => [
                'pdoSqlite' => [
                    'status' => $pdoOk ? 'available' : 'unavailable',
                    'message' => $pdoOk
                        ? 'PDO SQLite extension is loaded.'
                        : 'Install/enable pdo_sqlite to use the derived index.',
                ],
                'indexDirectory' => [
                    'status' => $dirWritable ? 'available' : 'failing',
                    'message' => $dirWritable
                        ? 'Index directory is writable.'
                        : 'data/index is not writable.',
                ],
                'sqliteFile' => [
                    'status' => $filePresent ? 'present' : 'missing',
                    'message' => $filePresent
                        ? 'content.sqlite exists (derived; safe to rebuild).'
                        : 'No SQLite file yet — run rebuild after enabling.',
                ],
                'integrity' => [
                    'status' => $integrityOk ? 'ok' : ($filePresent ? 'failing' : 'skipped'),
                    'message' => $integrityOk
                        ? 'PRAGMA integrity_check passed.'
                        : 'Rebuild recommended if integrity fails or file is missing.',
                ],
            ],
            'counts' => [
                'jsonEntries' => $this->contentIndex->countAllEntries(),
                'sqliteEntries' => $filePresent ? $this->store->entryCount() : 0,
            ],
        ];
    }

    public function runtimeReady(): bool
    {
        if (!extension_loaded('pdo_sqlite')) {
            return false;
        }

        if (!$this->indexDirectoryWritable()) {
            return false;
        }

        if (!is_readable($this->paths->absolutePath())) {
            return false;
        }

        return $this->store->integrityOk();
    }

    public function verifyActivation(): bool
    {
        if (!extension_loaded('pdo_sqlite') || !$this->indexDirectoryWritable()) {
            return false;
        }

        try {
            $this->rebuilder->rebuild();
        } catch (\Throwable) {
            return false;
        }

        return $this->parityWithJson();
    }

    public function parityWithJson(): bool
    {
        $jsonTotal = $this->contentIndex->countAllEntries();
        if ($jsonTotal !== $this->store->entryCount()) {
            return false;
        }

        $jsonPublishedArticles = $this->contentIndex->countMatching('article', ['status' => 'published']);
        $sqlitePublishedArticles = $this->countPublishedArticlesSqlite();

        return $jsonPublishedArticles === $sqlitePublishedArticles;
    }

    private function activeDriver(string $configured): string
    {
        if ($configured !== QueryIndexInterface::DRIVER_SQLITE) {
            return QueryIndexInterface::DRIVER_JSON;
        }

        return $this->runtimeReady() ? QueryIndexInterface::DRIVER_SQLITE : QueryIndexInterface::DRIVER_JSON;
    }

    private function indexDirectoryWritable(): bool
    {
        $dir = $this->paths->indexDirectory();
        if (!is_dir($dir)) {
            return mkdir($dir, 0755, true) || is_dir($dir);
        }

        return is_writable($dir);
    }

    private function countPublishedArticlesSqlite(): int
    {
        if (!is_readable($this->paths->absolutePath())) {
            return 0;
        }

        $pdo = $this->store->openConnection();
        $stmt = $pdo->query("SELECT slug, status, tags_json, locales_json, locale_status_json, default_locale FROM entries WHERE type = 'article'");
        if ($stmt === false) {
            return 0;
        }

        $count = 0;
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $entry = ContentIndexEntry::fromArray([
                'slug' => (string) ($row['slug'] ?? ''),
                'type' => 'article',
                'title' => '',
                'status' => (string) ($row['status'] ?? ''),
                'author' => '',
                'path' => '',
                'excerpt' => '',
                'tags' => json_decode((string) ($row['tags_json'] ?? '[]'), true) ?: [],
                'localeStatus' => json_decode((string) ($row['locale_status_json'] ?? '{}'), true) ?: [],
                'defaultLocale' => (string) ($row['default_locale'] ?? 'sk'),
                'updatedAt' => '',
                'createdAt' => '',
            ]);
            if ($entry->matchesStatusFilter('published')) {
                ++$count;
            }
        }

        return $count;
    }
}
