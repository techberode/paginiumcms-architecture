<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Backup\Contracts;

use PaginiumCMS\Core\Backup\Models\BackupMetadata;

interface BackupInterface
{
    /**
     * Vytvorí kompletnú zálohu systému.
     *
     * @param string $name Názov zálohy.
     * @param array $options Voliteľné parametre.
     * @return BackupMetadata Metadáta zálohy.
     */
    public function create(string $name, array $options = []): BackupMetadata;

    /**
     * Obnoví systém zo zálohy.
     *
     * @param string $backupId ID zálohy alebo cesta k súboru.
     * @param array $options Voliteľné parametre.
     * @return bool TRUE ak bola obnova úspešná.
     */
    public function restore(string $backupId, array $options = []): bool;

    /**
     * Získa zoznam dostupných záloh.
     *
     * @return array<int, BackupMetadata> Zoznam záloh.
     */
    public function listBackups(): array;

    /**
     * Získa metadáta konkrétnej zálohy.
     *
     * @param string $backupId ID zálohy.
     * @return BackupMetadata|null Metadáta zálohy alebo null.
     */
    public function getBackup(string $backupId): ?BackupMetadata;

    /**
     * Vymaže zálohu.
     *
     * @param string $backupId ID zálohy.
     * @return bool TRUE ak bolo vymazanie úspešné.
     */
    public function deleteBackup(string $backupId): bool;

    /**
     * Exportuje zálohu ako ZIP súbor na stiahnutie.
     *
     * @param string $backupId ID zálohy.
     * @return string Cesta k ZIP súboru.
     */
    public function exportBackup(string $backupId): string;

    /**
     * Importuje zálohu z nahraného ZIP súboru.
     *
     * @param string $filePath Cesta k ZIP súboru.
     * @return bool TRUE ak bol import úspešný.
     */
    public function importBackup(string $filePath): bool;

    /**
     * Naplánuje automatické zálohovanie.
     *
     * @param string $interval Interval (daily, weekly, monthly).
     * @param int $keep Počet záloh na uchovanie.
     */
    public function scheduleBackup(string $interval, int $keep = 7): void;

    /**
     * Získa informácie o naplánovaných zálohách.
     *
     * @return array Informácie o pláne.
     */
    public function getScheduleInfo(): array;
}
