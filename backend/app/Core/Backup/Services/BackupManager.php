<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Backup\Services;

use PaginiumCMS\Core\Backup\Contracts\BackupInterface;
use PaginiumCMS\Core\Backup\Models\BackupMetadata;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;

class BackupManager implements BackupInterface
{
    private FileReaderInterface $reader;
    private FileWriterInterface $writer;
    private string $backupPath;
    private string $contentPath;
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
        $this->contentPath = rtrim($contentPath, '/');
    }

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
        $zip->addFromString('backup.json', json_encode($metadata->jsonSerialize(), JSON_PRETTY_PRINT));

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
            if ($size === false || $size === null) {
                $size = 0;
            }
        }
        $metadata->setSize((int)$size);

        // Aktualizácia metadát
        $metadata->setFilePath($fullPath);
        $metadata->setSize(filesize($fullPath));
        $metadata->setStatus('completed');

        // Uloženie metadát
        $this->saveMetadata($metadata);

        return $metadata;
    }

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

    public function listBackups(): array
    {
        $backups = [];
        $metadataFiles = glob($this->backupPath . '/*.json');

        foreach ($metadataFiles as $file) {
            try {
                $content = file_get_contents($file);
                $data = json_decode($content, true);
                if ($data) {
                    $metadata = new BackupMetadata();
                    $metadata->setName($data['name'] ?? '');
                    // createdAt sa nastavuje v __construct, nevoláme setCreatedAt()
                    // $metadata->setCreatedAt($data['createdAt'] ?? date('Y-m-d H:i:s'));
                    $metadata->setSize($data['size'] ?? 0);
                    $metadata->setFilePath($data['filePath'] ?? '');
                    $metadata->setStatus($data['status'] ?? 'completed');
                    $backups[] = $metadata;
                }
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
            $content = file_get_contents($metadataPath);
            $data = json_decode($content, true);
            if (!$data) {
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

    public function getScheduleInfo(): array
    {
        $schedulePath = $this->backupPath . '/schedule.json';
        if (!file_exists($schedulePath)) {
            return ['enabled' => false];
        }

        $content = file_get_contents($schedulePath);
        $data = json_decode($content, true);
        return $data ?: ['enabled' => false];
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
                copy($sourcePath, $destPath);
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
        file_put_contents($path, json_encode($metadata->jsonSerialize(), JSON_PRETTY_PRINT));
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
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
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
