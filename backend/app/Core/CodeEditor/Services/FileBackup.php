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
        sort($files, SORT_STRING);

        return array_reverse($files);
    }

    public function restore(string $path, string $backupFile): bool
    {
        $resolved = $this->resolveBackupByBasename($path, basename($backupFile));
        if ($resolved === null) {
            return false;
        }

        $fullPath = $this->projectRoot . '/' . ltrim($path, '/');

        return copy($resolved, $fullPath);
    }

    public function resolveBackupByBasename(string $path, string $basename): ?string
    {
        $basename = basename(str_replace('\\', '/', $basename));
        $expectedPrefix = md5($path) . '_';

        if (!str_starts_with($basename, $expectedPrefix) || !str_ends_with($basename, '.bak')) {
            return null;
        }

        $full = $this->backupPath . '/' . $basename;

        return is_file($full) ? $full : null;
    }
}
