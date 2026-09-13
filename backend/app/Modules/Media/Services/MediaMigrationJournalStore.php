<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Modules\Media\Exception\MediaMigrationException;
use PaginiumCMS\Support\JsonHelper;

/**
 * Flat-file migration journal for media storage driver cutover (Iteration 72c).
 */
final class MediaMigrationJournalStore
{
    public const JOURNAL_PATH = 'media/migration/journal.json';

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function load(): ?array
    {
        if (!$this->reader->exists(self::JOURNAL_PATH)) {
            return null;
        }

        try {
            $content = $this->reader->read(self::JOURNAL_PATH);
            $data = json_decode($content, true);

            return is_array($data) ? $data : null;
        } catch (FlatFileException) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $journal
     */
    public function save(array $journal): void
    {
        $json = JsonHelper::encode($journal, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $this->writer->write(self::JOURNAL_PATH, $json, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function requireMatching(string $migrationId): array
    {
        $journal = $this->load();
        if ($journal === null) {
            throw new MediaMigrationException('No media migration journal found.');
        }

        if (($journal['id'] ?? '') !== $migrationId) {
            throw new MediaMigrationException('Migration id does not match the active journal.');
        }

        return $journal;
    }

    public static function sanitizeMigrationId(string $migrationId): string
    {
        $migrationId = trim($migrationId);
        if ($migrationId === '' || !preg_match('/^[a-zA-Z0-9_-]{3,64}$/', $migrationId)) {
            throw new MediaMigrationException('Invalid migration id.');
        }

        return $migrationId;
    }

    public static function proposeMigrationId(): string
    {
        return 'media_mig_' . gmdate('Ymd_His') . '_' . bin2hex(random_bytes(3));
    }
}
