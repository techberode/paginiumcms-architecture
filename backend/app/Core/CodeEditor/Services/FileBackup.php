<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\CodeEditor\Services;

/**
 * backend/app/Core/CodeEditor/Services/FileBackup.php
 *
 * OPRAVA (audit 12.7.2026): pôvodný default `'storage/backups/code'` bol
 * relatívna cesta použitá priamo v `is_dir()`/`mkdir()` bez prepočtu na
 * absolútnu - záviselo to od aktuálneho pracovného adresára PHP-FPM
 * procesu (na rozdiel od FlatFile FileReader/FileWriter, ktoré vždy idú
 * cez FileValidator s pevnou absolútnou base cestou). Teraz sa cesta buď
 * odovzdá explicitne (viď CodeEditorManager, ktorý ju skladá z
 * `$this->projectRoot`), alebo sa dopočíta absolútne tu.
 *
 * Doplnená aj kontrola v `restore()` - predtým prijímala ľubovoľnú
 * cieľovú cestu bez akéhokoľvek obmedzenia.
 */
class FileBackup
{
    private string $backupPath;

    public function __construct(?string $backupPath = null)
    {
        $this->backupPath = rtrim(
            $backupPath ?? (__DIR__ . '/../../../../../storage/backups/code'),
            '/'
        );
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
    }

    public function create(string $path): void
    {
        $fullPath = __DIR__ . '/../../../../../' . $path;
        if (!file_exists($fullPath)) {
            return;
        }

        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $this->backupPath . '/' . md5($path) . '_' . $timestamp . '.bak';
        copy($fullPath, $backupFile);
    }

    public function getBackups(string $path): array
    {
        $pattern = $this->backupPath . '/' . md5($path) . '_*.bak';
        $files = glob($pattern);
        sort($files, SORT_STRING | SORT_DESC);
        return $files;
    }

    /**
     * OPRAVA: restore() predtým prijímala $path bez validácie a priamo
     * naň kopírovala obsah zálohy. CodeEditorManager::canEdit() teraz
     * MUSÍ byť zavolaná volajúcim pred týmto krokom (rovnako ako pri
     * writeFile) - táto trieda samotná nemá prístup k allow/deny
     * zoznamu, takže kontrolu nevie zopakovať sama; zodpovednosť
     * zostáva na CodeEditorManager, ktorý ako jediný smie túto metódu
     * volať priamo.
     */
    public function restore(string $path, string $backupFile): bool
    {
        if (!str_starts_with(realpath($backupFile) ?: '', realpath($this->backupPath) ?: "\0")) {
            return false;
        }

        $fullPath = __DIR__ . '/../../../../../' . $path;
        return copy($backupFile, $fullPath);
    }
}
