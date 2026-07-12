// backend/app/Core/CodeEditor/Services/FileBackup.php
<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\CodeEditor\Services;

class FileBackup
{
    private string $backupPath;

    public function __construct(string $backupPath = 'storage/backups/code')
    {
        $this->backupPath = rtrim($backupPath, '/');
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
    }

    public function create(string $path): void
    {
        $fullPath = __DIR__ . '/../../../' . $path;
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

    public function restore(string $path, string $backupFile): bool
    {
        $fullPath = __DIR__ . '/../../../' . $path;
        return copy($backupFile, $fullPath);
    }
}
