<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Backup\Services;

use PaginiumCMS\Core\Backup\Contracts\BackupInterface;
use PaginiumCMS\Core\Backup\Models\BackupMetadata;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Support\FileHelper;
use PaginiumCMS\Support\JsonHelper;

class BackupManager implements BackupInterface
{
    private FileReaderInterface $reader;
    private FileWriterInterface $writer;
    private string $backupPath;
    private string $contentPath;
    /** @var array<int|string, mixed> */
    private array $excludePatterns = [
        '*.tmp',
        '*.cache',
        '*.lock',
        '*.backup.*',
    ];

    public function __construct(
        FileReaderInterface $reader,
        FileWriterInterface $writer,
        string $backupPath = 'storage/backups',
        string $contentPath = 'storage/app/content'
    ) {
        $this->reader = $reader;
        $this->writer = $writer;
        $this->backupPath = rtrim($backupPath, '/');
        $this->contentPath = rtrim($contentPath !== 'storage/app/content' ? $contentPath : $this->reader->getBasePath(), '/');
    }

    /**
     * @param array<int|string, mixed> $options
     */
    public function create(string $name, array $options = []): BackupMetadata
    {
        $metadata = new BackupMetadata();
        $metadata->setName($name);
        $metadata->setIncludes($options['includes'] ?? ['content', 'config', 'data']);

        $timestamp = date('Y-m-d_H-i-s');
        $filename = $timestamp . '_' . $this->sanitizeName($name) . '.zip';
        $fullPath = $this->backupPath . '/' . $filename;

        // Vytvorenie adresára
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }

        // Vytvorenie ZIP archívu
        $zip = new \ZipArchive();
        if ($zip->open($fullPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Nepodarilo sa vytvoriť ZIP archív');
        }

        // Pridanie metadát
        $zip->addFromString('backup.json', JsonHelper::encode($metadata->jsonSerialize()));

        // Pridanie obsahu
        foreach ($metadata->getIncludes() as $include) {
            $this->addDirectoryToZip($zip, $this->contentPath . '/' . $include, $include);
        }

        // Pridanie konfigurácie
        $this->addConfigToZip($zip);

        $zip->close();

        // Kontrola, či súbor existuje a má správnu veľkosť
        // Kontrola veľkosti súboru
        $size = 0;
        if (file_exists($fullPath)) {
            clearstatcache(true, $fullPath);
            $size = @filesize($fullPath);
            if ($size === false) {
                $size = 0;
            }
        }
        $metadata->setSize((int)$size);

        // Aktualizácia metadát
        $metadata->setFilePath($fullPath);
        $metadata->setSize($size);
        $metadata->setStatus('completed');

        // Uloženie metadát
        $this->saveMetadata($metadata);

        return $metadata;
    }

    /**
     * @param array<int|string, mixed> $options
     */
    public function restore(string $backupId, array $options = []): bool
    {
        // Získanie metadát
        $metadata = $this->getBackup($backupId);
        if ($metadata === null) {
            // Skúsime ako cestu k súboru
            if (file_exists($backupId)) {
                return $this->importBackup($backupId);
            }
            return false;
        }

        $zipPath = $metadata->getFilePath();
        if (!file_exists($zipPath)) {
            return false;
        }

        return $this->importBackup($zipPath);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function listBackups(): array
    {
        $backups = [];
        $metadataFiles = glob($this->backupPath . '/*.json') ?: [];

        foreach ($metadataFiles as $file) {
            try {
                $data = JsonHelper::decode(FileHelper::read($file));
                if ($data === []) {
                    continue;
                }

                $metadata = new BackupMetadata();
                $metadata->setName($data['name'] ?? '');
                $metadata->setSize($data['size'] ?? 0);
                $metadata->setFilePath($data['filePath'] ?? '');
                $metadata->setStatus($data['status'] ?? 'completed');
                $backups[] = $metadata;
            } catch (\Exception) {
                continue;
            }
        }

        // Zoradenie podľa času (najnovšie prvé)
        usort($backups, function ($a, $b) {
            return strtotime($b->getCreatedAt()) - strtotime($a->getCreatedAt());
        });

        return $backups;
    }

    public function getBackup(string $backupId): ?BackupMetadata
    {
        $metadataPath = $this->backupPath . '/' . $backupId . '.json';
        if (!file_exists($metadataPath)) {
            return null;
        }

        try {
            $data = JsonHelper::decode(FileHelper::read($metadataPath));
            if ($data === []) {
                return null;
            }

            $metadata = new BackupMetadata();
            $metadata->setName($data['name'] ?? '');
            // createdAt sa nastavuje v __construct
            // $metadata->setCreatedAt($data['createdAt'] ?? date('Y-m-d H:i:s'));
            $metadata->setSize($data['size'] ?? 0);
            $metadata->setFilePath($data['filePath'] ?? '');
            $metadata->setStatus($data['status'] ?? 'completed');
            return $metadata;
        } catch (\Exception) {
            return null;
        }
    }

    public function deleteBackup(string $backupId): bool
    {
        $metadata = $this->getBackup($backupId);
        if ($metadata === null) {
            return false;
        }

        // Vymazanie ZIP súboru
        $zipPath = $metadata->getFilePath();
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }

        // Vymazanie metadát
        $metadataPath = $this->backupPath . '/' . $backupId . '.json';
        if (file_exists($metadataPath)) {
            unlink($metadataPath);
        }

        return true;
    }

    public function exportBackup(string $backupId): string
    {
        $metadata = $this->getBackup($backupId);
        if ($metadata === null) {
            throw new \RuntimeException('Záloha nebola nájdená');
        }

        return $metadata->getFilePath();
    }

    public function importBackup(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException('Súbor neexistuje: ' . $filePath);
        }

        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new \RuntimeException('Nepodarilo sa otvoriť ZIP archív');
        }

        // Extrahovanie do dočasného adresára
        $tempDir = sys_get_temp_dir() . '/paginium_restore_' . uniqid();
        mkdir($tempDir, 0755, true);
        $zip->extractTo($tempDir);
        $zip->close();

        // Obnova obsahu
        $contentDir = $tempDir . '/content';
        if (is_dir($contentDir)) {
            $this->restoreDirectory($contentDir, $this->contentPath);
        }

        // Obnova konfigurácie
        $configDir = $tempDir . '/config';
        if (is_dir($configDir)) {
            $this->restoreDirectory($configDir, dirname($this->contentPath) . '/config');
        }

        // Vyčistenie
        $this->removeDirectory($tempDir);

        return true;
    }

    public function scheduleBackup(string $interval, int $keep = 7): void
    {
        $schedule = [
            'interval' => $interval,
            'keep' => $keep,
            'last_run' => null,
            'next_run' => $this->calculateNextRun($interval),
        ];

        file_put_contents(
            $this->backupPath . '/schedule.json',
            json_encode($schedule, JSON_PRETTY_PRINT)
        );
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getScheduleInfo(): array
    {
        $schedulePath = $this->backupPath . '/schedule.json';
        if (!file_exists($schedulePath)) {
            return ['enabled' => false];
        }

        $data = JsonHelper::decode(FileHelper::read($schedulePath));
        return $data !== [] ? $data : ['enabled' => false];
    }

    /**
     * @return array{ran: bool, reason?: string, backup?: BackupMetadata}
     */
    public function runScheduledBackupIfDue(): array
    {
        $schedulePath = $this->backupPath . '/schedule.json';
        if (!file_exists($schedulePath)) {
            return ['ran' => false, 'reason' => 'no_schedule'];
        }

        $schedule = JsonHelper::decode(FileHelper::read($schedulePath));
        $nextRun = strtotime((string) ($schedule['next_run'] ?? ''));
        if ($nextRun === false || time() < $nextRun) {
            return ['ran' => false, 'reason' => 'not_due'];
        }

        $backup = $this->create('scheduled_' . date('Y-m-d_H-i-s'));
        $schedule['last_run'] = date('Y-m-d H:i:s');
        $schedule['next_run'] = $this->calculateNextRun((string) ($schedule['interval'] ?? 'daily'));
        file_put_contents($schedulePath, json_encode($schedule, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $keep = max(1, (int) ($schedule['keep'] ?? 7));
        $this->pruneOldBackups($keep);

        return ['ran' => true, 'backup' => $backup];
    }

    private function pruneOldBackups(int $keep): void
    {
        $backups = $this->listBackups();
        if (count($backups) <= $keep) {
            return;
        }

        usort($backups, static function (BackupMetadata $a, BackupMetadata $b): int {
            return strcmp($b->getCreatedAt(), $a->getCreatedAt());
        });

        foreach (array_slice($backups, $keep) as $old) {
            $this->deleteBackup($old->getId());
        }
    }

    private function addDirectoryToZip(\ZipArchive $zip, string $dir, string $prefix): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $dir . '/' . $file;
            $relativePath = $prefix . '/' . $file;

            // Kontrola vylúčenia
            if ($this->isExcluded($relativePath)) {
                continue;
            }

            if (is_file($path)) {
                $zip->addFile($path, $relativePath);
            } elseif (is_dir($path)) {
                $zip->addEmptyDir($relativePath);
                $this->addDirectoryToZip($zip, $path, $relativePath);
            }
        }
    }

    private function addConfigToZip(\ZipArchive $zip): void
    {
        $configPath = dirname($this->contentPath) . '/config';
        if (is_dir($configPath)) {
            $this->addDirectoryToZip($zip, $configPath, 'config');
        }
    }

    private function restoreDirectory(string $source, string $destination): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $files = scandir($source);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $sourcePath = $source . '/' . $file;
            $destPath = $destination . '/' . $file;

            if (is_file($sourcePath)) {
                $relativeDest = $this->toContentRelativePath($destPath);
                if ($relativeDest !== null) {
                    $this->writer->write($relativeDest, FileHelper::read($sourcePath), false);
                } else {
                    copy($sourcePath, $destPath);
                }
            } elseif (is_dir($sourcePath)) {
                $this->restoreDirectory($sourcePath, $destPath);
            }
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $dir . '/' . $file;
            if (is_file($path)) {
                unlink($path);
            } elseif (is_dir($path)) {
                $this->removeDirectory($path);
            }
        }

        rmdir($dir);
    }

    private function saveMetadata(BackupMetadata $metadata): void
    {
        $path = $this->backupPath . '/' . $metadata->getId() . '.json';
        file_put_contents($path, JsonHelper::encode($metadata->jsonSerialize()));
    }

    private function isExcluded(string $path): bool
    {
        foreach ($this->excludePatterns as $pattern) {
            if (fnmatch($pattern, basename($path))) {
                return true;
            }
        }
        return false;
    }

    private function sanitizeName(string $name): string
    {
        $sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);

        return $sanitized ?? $name;
    }

    private function toContentRelativePath(string $absolutePath): ?string
    {
        $contentRoot = rtrim($this->contentPath, '/') . '/';
        if (!str_starts_with($absolutePath, $contentRoot)) {
            return null;
        }

        return substr($absolutePath, strlen($contentRoot));
    }

    private function calculateNextRun(string $interval): string
    {
        $now = time();
        switch ($interval) {
            case 'daily':
                return date('Y-m-d H:i:s', strtotime('+1 day', $now));
            case 'weekly':
                return date('Y-m-d H:i:s', strtotime('+1 week', $now));
            case 'monthly':
                return date('Y-m-d H:i:s', strtotime('+1 month', $now));
            default:
                return date('Y-m-d H:i:s', strtotime('+1 day', $now));
        }
    }
}
