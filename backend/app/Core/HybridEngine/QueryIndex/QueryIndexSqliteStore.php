<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\HybridEngine\QueryIndex;

use PaginiumCMS\Core\FlatFile\Models\ContentIndexEntry;
use PDO;
use RuntimeException;

/**
 * Low-level SQLite writes for derived query index (It.92b/c).
 */
final class QueryIndexSqliteStore
{
    public function __construct(
        private QueryIndexPaths $paths
    ) {
    }

    /**
     * @param list<array<string, mixed>> $items
     */
    public function replaceAllFromEntries(array $items): int
    {
        if (!extension_loaded('pdo_sqlite')) {
            throw new RuntimeException('pdo_sqlite is not available.');
        }

        $absolutePath = $this->paths->absolutePath();
        if (file_exists($absolutePath)) {
            unlink($absolutePath);
        }

        $pdo = $this->openConnection($absolutePath);
        QueryIndexSchema::apply($pdo);

        $pdo->beginTransaction();
        try {
            $count = 0;
            foreach ($items as $row) {
                $this->upsertEntry($pdo, ContentIndexEntry::fromArray($row));
                ++$count;
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return $count;
    }

    public function upsertEntry(?PDO $pdo, ContentIndexEntry $entry): void
    {
        $pdo ??= $this->openOrCreate();
        $existingId = $this->findEntryId($pdo, $entry->type, $entry->slug);

        if ($existingId === null) {
            $this->insertEntry($pdo, $entry);
        } else {
            $this->updateEntry($pdo, $existingId, $entry);
        }
    }

    public function deleteByTypeSlug(string $type, string $slug): void
    {
        if (!is_readable($this->paths->absolutePath())) {
            return;
        }

        $pdo = $this->openConnection();
        $id = $this->findEntryId($pdo, $type, $slug);
        if ($id === null) {
            return;
        }

        $pdo->prepare('DELETE FROM entries_fts WHERE rowid = :id')->execute(['id' => $id]);
        $pdo->prepare('DELETE FROM entries WHERE id = :id')->execute(['id' => $id]);
    }

    public function deleteByTypePath(string $type, string $path): void
    {
        if ($path === '' || !is_readable($this->paths->absolutePath())) {
            return;
        }

        $pdo = $this->openConnection();
        $stmt = $pdo->prepare('SELECT id FROM entries WHERE type = :type AND path = :path LIMIT 1');
        $stmt->execute(['type' => $type, 'path' => $path]);
        $id = $stmt->fetchColumn();
        if ($id === false) {
            return;
        }

        $entryId = (int) $id;
        $pdo->prepare('DELETE FROM entries_fts WHERE rowid = :id')->execute(['id' => $entryId]);
        $pdo->prepare('DELETE FROM entries WHERE id = :id')->execute(['id' => $entryId]);
    }

    public function entryCount(): int
    {
        if (!is_readable($this->paths->absolutePath())) {
            return 0;
        }

        $stmt = $this->openConnection()->query('SELECT COUNT(*) FROM entries');
        if ($stmt === false) {
            return 0;
        }

        return (int) $stmt->fetchColumn();
    }

    public function integrityOk(): bool
    {
        if (!is_readable($this->paths->absolutePath())) {
            return false;
        }

        $stmt = $this->openConnection()->query('PRAGMA integrity_check');
        if ($stmt === false) {
            return false;
        }

        $result = $stmt->fetchColumn();

        return $result === 'ok';
    }

    private function openOrCreate(): PDO
    {
        $path = $this->paths->absolutePath();
        if (!file_exists($path)) {
            touch($path);
        }

        $pdo = $this->openConnection($path);
        QueryIndexSchema::apply($pdo);

        return $pdo;
    }

    public function openConnection(?string $absolutePath = null): PDO
    {
        $path = $absolutePath ?? $this->paths->absolutePath();
        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        QueryIndexRebuilder::applyPragmas($pdo);

        return $pdo;
    }

    private function findEntryId(PDO $pdo, string $type, string $slug): ?int
    {
        $stmt = $pdo->prepare('SELECT id FROM entries WHERE type = :type AND slug = :slug LIMIT 1');
        $stmt->execute(['type' => $type, 'slug' => $slug]);
        $id = $stmt->fetchColumn();
        if ($id === false) {
            return null;
        }

        return (int) $id;
    }

    private function insertEntry(PDO $pdo, ContentIndexEntry $entry): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO entries (
                slug, type, title, status, author, path, excerpt, category,
                updated_at, created_at, scheduled_at, last_reviewed_at, default_locale,
                tags_json, locales_json, locale_status_json, calendar_date
            ) VALUES (
                :slug, :type, :title, :status, :author, :path, :excerpt, :category,
                :updated_at, :created_at, :scheduled_at, :last_reviewed_at, :default_locale,
                :tags_json, :locales_json, :locale_status_json, :calendar_date
            )'
        );
        $stmt->execute($this->bindEntry($entry));
        $rowId = (int) $pdo->lastInsertId();
        $this->syncFts($pdo, $rowId, $entry);
    }

    private function updateEntry(PDO $pdo, int $id, ContentIndexEntry $entry): void
    {
        $stmt = $pdo->prepare(
            'UPDATE entries SET
                title = :title, status = :status, author = :author, path = :path,
                excerpt = :excerpt, category = :category, updated_at = :updated_at,
                created_at = :created_at, scheduled_at = :scheduled_at,
                last_reviewed_at = :last_reviewed_at, default_locale = :default_locale,
                tags_json = :tags_json, locales_json = :locales_json,
                locale_status_json = :locale_status_json, calendar_date = :calendar_date
             WHERE id = :id'
        );
        $params = $this->bindEntry($entry);
        $params['id'] = $id;
        $stmt->execute($params);
        $pdo->prepare('DELETE FROM entries_fts WHERE rowid = :id')->execute(['id' => $id]);
        $this->syncFts($pdo, $id, $entry);
    }

    private function syncFts(PDO $pdo, int $rowId, ContentIndexEntry $entry): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO entries_fts (rowid, title, slug, excerpt, tags_text)
             VALUES (:rowid, :title, :slug, :excerpt, :tags_text)'
        );
        $stmt->execute([
            'rowid' => $rowId,
            'title' => $entry->title,
            'slug' => $entry->slug,
            'excerpt' => $entry->excerpt,
            'tags_text' => implode(' ', $entry->tags),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function bindEntry(ContentIndexEntry $entry): array
    {
        return [
            'slug' => $entry->slug,
            'type' => $entry->type,
            'title' => $entry->title,
            'status' => $entry->status,
            'author' => $entry->author,
            'path' => $entry->path,
            'excerpt' => $entry->excerpt,
            'category' => $entry->category,
            'updated_at' => $entry->updatedAt,
            'created_at' => $entry->createdAt,
            'scheduled_at' => $entry->scheduledAt,
            'last_reviewed_at' => $entry->lastReviewedAt,
            'default_locale' => $entry->defaultLocale,
            'tags_json' => json_encode($entry->tags, JSON_UNESCAPED_UNICODE) ?: '[]',
            'locales_json' => json_encode($entry->locales, JSON_UNESCAPED_UNICODE) ?: '[]',
            'locale_status_json' => json_encode($entry->localeStatus, JSON_UNESCAPED_UNICODE) ?: '{}',
            'calendar_date' => $entry->calendarDate(),
        ];
    }
}
