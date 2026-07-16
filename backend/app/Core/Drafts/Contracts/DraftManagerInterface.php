<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Drafts\Contracts;

use PaginiumCMS\Core\Drafts\Models\Draft;

/**
 * === Kontrakt: DraftManagerInterface ===
 * Správa rozpracovaných konceptov (auto-save) vo flat-file úložisku.
 * Koncepty sú oddelené od publikovaného obsahu (`data/drafts/`).
 */
interface DraftManagerInterface
{
    /**
     * Uloží (alebo prepíše) koncept.
     *
     * @param array<int|string, mixed> $payload  Údaje konceptu (title, content, status, baseRevision).
 */public function save(string $type, string $slug, array $payload, string $userId): Draft;

    /**
     * Načíta koncept, alebo null ak neexistuje.
     */
    public function get(string $type, string $slug): ?Draft;

    /**
     * Zistí, či existuje koncept.
     */
    public function exists(string $type, string $slug): bool;

    /**
     * Zmaže koncept (napr. po úspešnom publikovaní obsahu).
     */
    public function discard(string $type, string $slug): void;
}
