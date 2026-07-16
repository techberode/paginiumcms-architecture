<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Audit\Contracts;

use PaginiumCMS\Modules\Audit\Models\AuditIssue;

/**
 * Rozhranie pre jednotlivého auditora.
 */
interface AuditorInterface
{
    /**
     * Získa názov auditora.
     *
     * @return string Názov auditora.
     */
    public function getName(): string;

    /**
     * Získa popis auditora.
     *
     * @return string Popis auditora.
     */
    public function getDescription(): string;

    /**
     * Spustí audit a vráti zoznam problémov.
     *
     * @param array<int|string, mixed> $options Voliteľné parametre.
     * @return array<int, AuditIssue> Zoznam nájdených problémov.
 */public function run(array $options = []): array;
}
