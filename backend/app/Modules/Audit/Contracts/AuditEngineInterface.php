<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Audit\Contracts;

use PaginiumCMS\Modules\Audit\Models\AuditReport;

/**
 * Rozhranie pre Audit Engine.
 */
interface AuditEngineInterface
{
    /**
     * Spustí kompletný audit.
     *
     * @param array<int|string, mixed> $options Voliteľné parametre.
     * @return AuditReport Správa z auditu.
 */public function run(array $options = []): AuditReport;

    /**
     * Spustí iba vybrané audity.
     *
     * @param array<int, string> $auditors Zoznam auditorov na spustenie.
     * @return AuditReport Správa z auditu.
 */public function runSelected(array $auditors): AuditReport;

    /**
     * Získa zoznam dostupných auditorov.
     *
     * @return array<int, string> Zoznam názvov auditorov.
 */public function getAvailableAuditors(): array;

    /**
     * Pridá vlastného auditora.
     *
     * @param AuditorInterface $auditor Inštancia auditora.
     */
    public function addAuditor(AuditorInterface $auditor): void;
}
