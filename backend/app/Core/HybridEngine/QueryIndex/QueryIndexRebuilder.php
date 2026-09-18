<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\HybridEngine\QueryIndex;

use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PDO;
use RuntimeException;

/**
 * Full rebuild of content.sqlite from the JSON index snapshot (It.92b).
 */
final class QueryIndexRebuilder
{
    public function __construct(
        private ContentIndexService $contentIndex,
        private QueryIndexSqliteStore $store
    ) {
    }

    public function rebuild(): int
    {
        if (!extension_loaded('pdo_sqlite')) {
            throw new RuntimeException('pdo_sqlite extension is not available.');
        }

        return $this->store->replaceAllFromEntries($this->contentIndex->snapshotItems());
    }

    public function openConnection(?string $absolutePath = null): PDO
    {
        return $this->store->openConnection($absolutePath);
    }

    public static function applyPragmas(PDO $pdo): void
    {
        $pdo->exec('PRAGMA journal_mode=WAL');
        $pdo->exec('PRAGMA busy_timeout=5000');
        $pdo->exec('PRAGMA foreign_keys=ON');
    }
}
