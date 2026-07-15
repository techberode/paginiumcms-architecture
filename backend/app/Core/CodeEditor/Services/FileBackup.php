<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\CodeEditor\Services;

/**
 * Per-file backups for CodeEditor writes (Iteration 14).
 */
final class FileBackup
{
    private string $projectRoot;
    private string $backupPath;

    public function __construct(?string $projectRoot = null, ?string $backupPath = null)
    {
        $this->projectRoot = rtrim($projectRoot ?? dirname(__DIR__, 5), '/');
        $this->backupPath = rtrim(
            $backupPath ?? ($this->projectRoot . '/storage/backups/code'),
            '/'
        );

        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
    }

    public function create(string $path): void
    {
        $fullPath = $this->projectRoot . '/' . ltrim($path, '/');
        if (!file_exists($fullPath)) {
            return;
        }

        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $this->backupPath . '/' . md5($path) . '_' . $timestamp . '.bak';
        copy($fullPath, $backupFile);
    }

    /**
     * @return list<string>
     */
    public function getBackups(string $path): array
    {
        $pattern = $this->backupPath . '/' . md5($path) . '_*.bak';
        $files = glob($pattern) ?: [];
        sort($files, SORT_STRING | SORT_DESC);

        return $files;
    }

    public function restore(string $path, string $backupFile): bool
    {
        $backupReal = realpath($backupFile);
        $backupRoot = realpath($this->backupPath);
        if ($backupReal === false || $backupRoot === false || !str_starts_with($backupReal, $backupRoot)) {
            return false;
        }

        $fullPath = $this->projectRoot . '/' . ltrim($path, '/');

        return copy($backupFile, $fullPath);
    }
}
