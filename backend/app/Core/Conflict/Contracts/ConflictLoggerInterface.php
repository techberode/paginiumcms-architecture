<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Conflict\Contracts;

use PaginiumCMS\Core\Conflict\Models\ConflictRecord;

/**
 * === Kontrakt: ConflictLoggerInterface ===
 * Flat-file log konfliktov obsahu (Iterácia 3). Slúži pre admin prehľad a audit.
 */
interface ConflictLoggerInterface
{
    /**
     * Zaznamená konflikt.
     */
    public function record(ConflictRecord $record): void;

    /**
     * Vráti najnovšie konflikty (zostupne podľa času).
     *
     * @return array<int, ConflictRecord>
 */public function getRecent(int $limit = 100): array;

    /**
     * Vymaže celý log konfliktov.
     */
    public function clear(): void;
}
