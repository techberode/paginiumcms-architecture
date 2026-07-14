<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Logging\Contracts;

use PaginiumCMS\Core\Logging\Models\LogEntry;

/**
 * Rozhranie pre zápis logov.
 */
interface LogWriterInterface
{
    /**
     * Zapíše logovaciu položku.
     */
    public function write(LogEntry $entry): void;

    /**
     * Získa všetky logovacie položky.
     */
    public function readAll(): array;

    /**
     * Získa posledné logovacie položky.
     */
    public function readLast(int $limit = 100): array;

    /**
     * Získa logovacie položky podľa priority.
     */
    public function readBySeverity(string $severity, int $limit = 100): array;

    /**
     * Získa logovacie položky podľa kategórie.
     */
    public function readByCategory(string $category, int $limit = 100): array;

    /**
     * Vymaže staré logovacie položky.
     */
    public function clearOld(int $days = 30): int;
}
