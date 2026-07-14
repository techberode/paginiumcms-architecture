<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Audit\Contracts;

use PaginiumCMS\Modules\Audit\Models\AuditIssue;

/**
 * Rozhranie pre správu z auditu.
 */
interface AuditReportInterface
{
    /**
     * Získa všetky problémy.
     *
     * @return array<int, AuditIssue> Zoznam problémov.
     */
    public function getIssues(): array;

    /**
     * Získa problémy podľa závažnosti.
     *
     * @param string $severity Závažnosť ('critical', 'error', 'warning', 'info').
     * @return array<int, AuditIssue> Zoznam problémov.
     */
    public function getIssuesBySeverity(string $severity): array;

    /**
     * Získa počet problémov.
     *
     * @return int Celkový počet problémov.
     */
    public function getTotalIssues(): int;

    /**
     * Získa počet problémov podľa závažnosti.
     *
     * @return array<string, int> Počet problémov podľa závažnosti.
     */
    public function getSeverityCounts(): array;

    /**
     * Zistí, či audit prešiel (žiadne chyby alebo kritické problémy).
     *
     * @return bool TRUE ak audit prešiel.
     */
    public function isPassed(): bool;

    /**
     * Exportuje správu do poľa.
     *
     * @return array<string, mixed> Dáta správy.
     */
    public function toArray(): array;

    /**
     * Exportuje správu do JSON.
     *
     * @return string JSON reťazec.
     */
    public function toJson(): string;
}
