<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Backup\Services;

use PaginiumCMS\Core\Backup\Contracts\BackupInterface;
use PaginiumCMS\Core\Backup\Models\BackupMetadata;
use PaginiumCMS\Core\Cache\ContentCacheService;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Core\Security\Services\ZipEntryGuard;
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
        string $contentPath = 'storage/app/content',
        private ?ContentCacheService $contentCache = null,
        private ?ContentIndexService $contentIndex = null,
        private ?ContentRepositoryInterface $contentRepository = null,
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
        $includes = BackupScope::normalizeIncludes(
            isset($options['includes']) && is_array($options['includes'])
                ? $options['includes']
                : BackupScope::DEFAULT_INCLUDES
        );
        $requestedMode = BackupScope::normalizeMode($options['mode'] ?? BackupScope::MODE_FULL);

        $liveFiles = $this->collectScopedFiles($includes);
        $baselineHashes = [];
        $base = null;
        $mode = $requestedMode;
        if ($mode === BackupScope::MODE_INCREMENTAL) {
            $base = $this->findIncrementalBase($includes);
            if ($base === null) {
                $mode = BackupScope::MODE_FULL;
            } else {
                $baselineHashes = $this->loadManifestHashes($base);
            }
        }

        $metadata = new BackupMetadata();
        $metadata->setName($name);
        $metadata->setIncludes($includes);
        $metadata->setMode($mode);
        $metadata->setBaseBackupId($base !== null ? $base->getId() : '');
        $metadata->setFilesTotal(count($liveFiles));

        $timestamp = date('Y-m-d_H-i-s');
        $filename = $timestamp . '_' . $this->sanitizeName($name) . '.zip';
        $fullPath = $this->backupPath . '/' . $filename;

        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }

        $zip = new \ZipArchive();
        if ($zip->open($fullPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Nepodarilo sa vytvoriť ZIP archív');
        }

        $packed = 0;
        foreach ($liveFiles as $zipPath => $info) {
            $unchanged = $mode === BackupScope::MODE_INCREMENTAL
                && isset($baselineHashes[$zipPath])
                && hash_equals($baselineHashes[$zipPath], $info['sha256']);
            if ($unchanged) {
                continue;
            }
            $zip->addFile($info['absolute'], $zipPath);
            $packed++;
        }

        $deletes = [];
        if ($mode === BackupScope::MODE_INCREMENTAL) {
            foreach ($baselineHashes as $zipPath => $_hash) {
                if (!isset($liveFiles[$zipPath])) {
                    $deletes[] = $zipPath;
                }
            }
        }

        $metadata->setFilesPacked($packed);
        $zip->addFromString('backup.json', JsonHelper::encode($metadata->jsonSerialize()));
        $zip->addFromString('manifest.json', JsonHelper::encode($this->manifestPayload($liveFiles, $deletes)));
        if ($deletes !== []) {
            $zip->addFromString('deletes.json', JsonHelper::encode(['paths' => $deletes]));
        }

        $zip->close();

        $size = 0;
        if (file_exists($fullPath)) {
            clearstatcache(true, $fullPath);
            $size = @filesize($fullPath);
            if ($size === false) {
                $size = 0;
            }
        }
        $metadata->setSize((int) $size);
        $metadata->setFilePath($fullPath);
        $metadata->setStatus('completed');
        $metadata->setSha256(hash_file('sha256', $fullPath) ?: '');

        $this->saveMetadata($metadata);
        $this->saveManifestSidecar($metadata, $liveFiles, $deletes);

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

        foreach ($this->resolveRestoreChain($metadata) as $item) {
            if (!$this->importBackup($item->getFilePath())) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function listBackups(): array
    {
        $backups = [];
        $metadataFiles = glob($this->backupPath . '/*.json') ?: [];

        foreach ($metadataFiles as $file) {
            $basename = basename($file);
            if ($basename === 'schedule.json' || str_ends_with($basename, '.manifest.json')) {
                continue;
            }

            try {
                $data = JsonHelper::decode(FileHelper::read($file));
                if ($data === [] || !isset($data['id'], $data['filePath'])) {
                    continue;
                }

                $backups[] = $this->hydrateMetadata($data);
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

            return $this->hydrateMetadata($data);
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

        $manifestPath = $this->backupPath . '/' . $backupId . '.manifest.json';
        if (file_exists($manifestPath)) {
            unlink($manifestPath);
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

        $zipGuard = new ZipEntryGuard();
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = (string) $zip->getNameIndex($i);
            if (!$zipGuard->isSafeEntry($entryName)) {
                $zip->close();
                throw new \RuntimeException('Nebezpečný ZIP záznam odmietnutý: ' . $entryName);
            }
        }

        // Extrahovanie do dočasného adresára
        $tempDir = sys_get_temp_dir() . '/paginium_restore_' . uniqid();
        mkdir($tempDir, 0755, true);
        $zip->extractTo($tempDir);
        $zip->close();

        // Obnova obsahu — current format: content/{pages,blog,media,data,…}
        $contentDir = $tempDir . '/content';
        if (is_dir($contentDir)) {
            $this->restoreDirectory($contentDir, $this->contentPath);
        }

        // Legacy backups (≤ beta.65): only data/ at ZIP root — settings, indexes, analytics
        $legacyDataDir = $tempDir . '/data';
        if (is_dir($legacyDataDir)) {
            $this->restoreDirectory($legacyDataDir, $this->contentPath . '/data');
        }

        // Obnova konfigurácie
        $configDir = $tempDir . '/config';
        if (is_dir($configDir)) {
            $this->restoreDirectory($configDir, dirname($this->contentPath) . '/config');
        }

        $this->applyDeclaredDeletes($tempDir . '/deletes.json');

        // Vyčistenie
        $this->removeDirectory($tempDir);

        $this->afterContentRestore();

        return true;
    }

    public function registerArchive(string $filePath, string $name): BackupMetadata
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException('Súbor neexistuje: ' . $filePath);
        }

        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new \RuntimeException('Neplatný ZIP archív');
        }
        $zip->close();

        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }

        $metadata = new BackupMetadata();
        $metadata->setName($name);
        $metadata->setIncludes(['content', 'config', 'data']);

        $timestamp = date('Y-m-d_H-i-s');
        $filename = $timestamp . '_import_' . $this->sanitizeName($name) . '.zip';
        $destination = $this->backupPath . '/' . $filename;

        if (!copy($filePath, $destination)) {
            throw new \RuntimeException('Nepodarilo sa uložiť importovaný archív');
        }

        $size = (int) filesize($destination);
        $metadata->setFilePath($destination);
        $metadata->setSize($size);
        $metadata->setStatus('completed');
        $metadata->setSha256(hash_file('sha256', $destination) ?: '');
        $this->saveMetadata($metadata);

        return $metadata;
    }

    /**
     * @return array{valid: bool, expected: string, actual: ?string, reason?: string}
     */
    public function verifyIntegrity(string $backupId): array
    {
        $metadata = $this->getBackup($backupId);
        if ($metadata === null) {
            throw new \RuntimeException('Záloha nebola nájdená');
        }

        $expected = $metadata->getSha256();
        $path = $metadata->getFilePath();
        if (!is_file($path)) {
            return [
                'valid' => false,
                'expected' => $expected,
                'actual' => null,
                'reason' => 'file_missing',
            ];
        }

        if ($expected === '') {
            return [
                'valid' => true,
                'expected' => '',
                'actual' => hash_file('sha256', $path) ?: '',
                'reason' => 'legacy_without_hash',
            ];
        }

        $actual = hash_file('sha256', $path) ?: '';

        return [
            'valid' => hash_equals($expected, $actual),
            'expected' => $expected,
            'actual' => $actual,
        ];
    }

    public function scheduleBackup(string $interval, int $keep = 7, array $options = []): void
    {
        $includes = BackupScope::normalizeIncludes(
            isset($options['includes']) && is_array($options['includes'])
                ? $options['includes']
                : BackupScope::DEFAULT_INCLUDES
        );
        $mode = BackupScope::normalizeMode($options['mode'] ?? BackupScope::MODE_FULL);

        $schedule = [
            'enabled' => true,
            'interval' => $interval,
            'keep' => $keep,
            'includes' => $includes,
            'mode' => $mode,
            'last_run' => null,
            'next_run' => $this->calculateNextRun($interval),
        ];

        file_put_contents(
            $this->backupPath . '/schedule.json',
            JsonHelper::encode($schedule)
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
        if ($data === []) {
            return ['enabled' => false];
        }

        $data['enabled'] = true;
        $data['includes'] = BackupScope::normalizeIncludes(
            isset($data['includes']) && is_array($data['includes'])
                ? $data['includes']
                : BackupScope::DEFAULT_INCLUDES
        );
        $data['mode'] = BackupScope::normalizeMode($data['mode'] ?? BackupScope::MODE_FULL);

        return $data;
    }

    public function clearSchedule(): void
    {
        $schedulePath = $this->backupPath . '/schedule.json';
        if (is_file($schedulePath)) {
            unlink($schedulePath);
        }
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

        $includes = BackupScope::normalizeIncludes(
            isset($schedule['includes']) && is_array($schedule['includes'])
                ? $schedule['includes']
                : BackupScope::DEFAULT_INCLUDES
        );
        $mode = BackupScope::normalizeMode($schedule['mode'] ?? BackupScope::MODE_FULL);

        $backup = $this->create('scheduled_' . date('Y-m-d_H-i-s'), [
            'includes' => $includes,
            'mode' => $mode,
        ]);
        $schedule['last_run'] = date('Y-m-d H:i:s');
        $schedule['next_run'] = $this->calculateNextRun((string) ($schedule['interval'] ?? 'daily'));
        $schedule['includes'] = $includes;
        $schedule['mode'] = $mode;
        file_put_contents($schedulePath, JsonHelper::encode($schedule));

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

        $protected = [];
        foreach (array_slice($backups, 0, $keep) as $kept) {
            $protected[$kept->getId()] = true;
            foreach ($this->ancestorIds($kept) as $ancestorId) {
                $protected[$ancestorId] = true;
            }
        }

        foreach ($backups as $old) {
            if (isset($protected[$old->getId()])) {
                continue;
            }
            $this->deleteBackup($old->getId());
        }
    }

    /**
     * @param array<int|string, mixed> $includes
     * @return array<string, array{absolute: string, sha256: string, size: int}>
     */
    private function collectScopedFiles(array $includes): array
    {
        $files = [];
        if (in_array('content', $includes, true)) {
            $this->collectDirectoryFiles($this->contentPath, 'content', $files);
        } else {
            foreach (BackupScope::CONTENT_SUBTREES as $subdir) {
                if (!in_array($subdir, $includes, true)) {
                    continue;
                }
                $absolute = $this->contentPath . '/' . $subdir;
                if (is_dir($absolute)) {
                    $this->collectDirectoryFiles($absolute, 'content/' . $subdir, $files);
                }
            }
        }

        if (in_array('config', $includes, true)) {
            $configPath = dirname($this->contentPath) . '/config';
            if (is_dir($configPath)) {
                $this->collectDirectoryFiles($configPath, 'config', $files);
            }
        }

        return $files;
    }

    /**
     * @param array<string, array{absolute: string, sha256: string, size: int}> $files
     */
    private function collectDirectoryFiles(string $dir, string $prefix, array &$files): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $entries = scandir($dir);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $dir . '/' . $file;
            $relativePath = $prefix . '/' . $file;
            if ($this->isExcluded($relativePath)) {
                continue;
            }

            if (is_file($path)) {
                $hash = hash_file('sha256', $path) ?: '';
                $size = filesize($path);
                $files[$relativePath] = [
                    'absolute' => $path,
                    'sha256' => $hash,
                    'size' => $size === false ? 0 : $size,
                ];
            } elseif (is_dir($path)) {
                $this->collectDirectoryFiles($path, $relativePath, $files);
            }
        }
    }

    /**
     * @param list<string> $includes
     */
    private function findIncrementalBase(array $includes): ?BackupMetadata
    {
        $signature = BackupScope::includesSignature($includes);
        foreach ($this->listBackups() as $backup) {
            if ($backup->getStatus() !== 'completed') {
                continue;
            }
            if (BackupScope::includesSignature($backup->getIncludes()) !== $signature) {
                continue;
            }

            return $backup;
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function loadManifestHashes(BackupMetadata $backup): array
    {
        $sidecar = $this->backupPath . '/' . $backup->getId() . '.manifest.json';
        if (is_file($sidecar)) {
            $decoded = JsonHelper::decode(FileHelper::read($sidecar));
            return $this->hashesFromManifest($decoded);
        }

        $zipPath = $backup->getFilePath();
        if (!is_file($zipPath)) {
            return [];
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return [];
        }
        $raw = $zip->getFromName('manifest.json');
        $zip->close();
        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = JsonHelper::decode($raw);

        return $this->hashesFromManifest($decoded);
    }

    /**
     * @param array<int|string, mixed> $manifest
     * @return array<string, string>
     */
    private function hashesFromManifest(array $manifest): array
    {
        $files = $manifest['files'] ?? null;
        if (!is_array($files)) {
            return [];
        }

        $hashes = [];
        foreach ($files as $path => $info) {
            if (!is_string($path) || $path === '') {
                continue;
            }
            if (is_array($info) && isset($info['sha256']) && is_string($info['sha256'])) {
                $hashes[$path] = $info['sha256'];
            }
        }

        return $hashes;
    }

    /**
     * @param array<string, array{absolute: string, sha256: string, size: int}> $liveFiles
     * @param list<string> $deletes
     * @return array{files: array<string, array{sha256: string, size: int}>, deletes: list<string>}
     */
    private function manifestPayload(array $liveFiles, array $deletes): array
    {
        $files = [];
        foreach ($liveFiles as $zipPath => $info) {
            $files[$zipPath] = [
                'sha256' => $info['sha256'],
                'size' => $info['size'],
            ];
        }

        return ['files' => $files, 'deletes' => $deletes];
    }

    /**
     * @param array<string, array{absolute: string, sha256: string, size: int}> $liveFiles
     * @param list<string> $deletes
     */
    private function saveManifestSidecar(BackupMetadata $metadata, array $liveFiles, array $deletes): void
    {
        $path = $this->backupPath . '/' . $metadata->getId() . '.manifest.json';
        file_put_contents($path, JsonHelper::encode($this->manifestPayload($liveFiles, $deletes)));
    }

    /**
     * @return list<BackupMetadata>
     */
    private function resolveRestoreChain(BackupMetadata $tip): array
    {
        $chain = [];
        $seen = [];
        $current = $tip;
        while (true) {
            if (isset($seen[$current->getId()])) {
                throw new \RuntimeException('Incremental backup chain contains a cycle');
            }
            $seen[$current->getId()] = true;
            array_unshift($chain, $current);
            if ($current->getMode() !== BackupScope::MODE_INCREMENTAL || $current->getBaseBackupId() === '') {
                break;
            }
            $parent = $this->getBackup($current->getBaseBackupId());
            if ($parent === null) {
                throw new \RuntimeException('Incremental backup is missing its full baseline');
            }
            $current = $parent;
        }

        return $chain;
    }

    /**
     * @return list<string>
     */
    private function ancestorIds(BackupMetadata $backup): array
    {
        $ids = [];
        $current = $backup;
        $seen = [];
        while ($current->getMode() === BackupScope::MODE_INCREMENTAL && $current->getBaseBackupId() !== '') {
            $parentId = $current->getBaseBackupId();
            if (isset($seen[$parentId])) {
                break;
            }
            $seen[$parentId] = true;
            $ids[] = $parentId;
            $parent = $this->getBackup($parentId);
            if ($parent === null) {
                break;
            }
            $current = $parent;
        }

        return $ids;
    }

    private function applyDeclaredDeletes(string $deletesFile): void
    {
        if (!is_file($deletesFile)) {
            return;
        }

        $decoded = JsonHelper::decode(FileHelper::read($deletesFile));
        $paths = $decoded['paths'] ?? [];
        if (!is_array($paths)) {
            return;
        }

        $zipGuard = new ZipEntryGuard();
        $contentRoot = rtrim($this->contentPath, '/') . DIRECTORY_SEPARATOR;
        $configRoot = rtrim(dirname($this->contentPath) . '/config', '/') . DIRECTORY_SEPARATOR;

        foreach ($paths as $zipPath) {
            if (!is_string($zipPath) || !$zipGuard->isSafeEntry($zipPath)) {
                continue;
            }

            $absolute = null;
            if (str_starts_with($zipPath, 'content/')) {
                $absolute = $this->contentPath . '/' . substr($zipPath, strlen('content/'));
            } elseif (str_starts_with($zipPath, 'config/')) {
                $absolute = dirname($this->contentPath) . '/config/' . substr($zipPath, strlen('config/'));
            }
            if ($absolute === null || !is_file($absolute)) {
                continue;
            }

            $normalized = str_replace('/', DIRECTORY_SEPARATOR, $absolute);
            $allowed = str_starts_with($normalized, $contentRoot) || str_starts_with($normalized, $configRoot);
            if (!$allowed) {
                continue;
            }

            @unlink($absolute);
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

    /**
     * @param array<int|string, mixed> $data
     */
    private function hydrateMetadata(array $data): BackupMetadata
    {
        $metadata = new BackupMetadata();
        if (!empty($data['id'])) {
            $metadata->setId((string) $data['id']);
        }
        $metadata->setName((string) ($data['name'] ?? ''));
        if (!empty($data['createdAt'])) {
            $metadata->setCreatedAt((string) $data['createdAt']);
        }
        $metadata->setSize((int) ($data['size'] ?? 0));
        $metadata->setFilePath((string) ($data['filePath'] ?? ''));
        $metadata->setStatus((string) ($data['status'] ?? 'completed'));
        if (!empty($data['includes']) && is_array($data['includes'])) {
            $metadata->setIncludes($data['includes']);
        }
        $metadata->setSha256((string) ($data['sha256'] ?? ''));
        $metadata->setMode((string) ($data['mode'] ?? 'full'));
        $metadata->setBaseBackupId((string) ($data['baseBackupId'] ?? ''));
        $metadata->setFilesPacked((int) ($data['filesPacked'] ?? 0));
        $metadata->setFilesTotal((int) ($data['filesTotal'] ?? 0));

        return $metadata;
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
        // FileWriter paths are relative to contentPath (same base as FileValidator).
        $contentRoot = rtrim($this->contentPath, '/') . DIRECTORY_SEPARATOR;
        $normalized = str_replace('/', DIRECTORY_SEPARATOR, $absolutePath);
        if (!str_starts_with($normalized, $contentRoot)) {
            return null;
        }

        return str_replace('\\', '/', substr($normalized, strlen($contentRoot)));
    }

    private function afterContentRestore(): void
    {
        if ($this->contentIndex !== null && $this->contentRepository !== null) {
            $this->contentIndex->rebuild($this->contentRepository);
        }

        $this->contentCache?->purgeAll();
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
