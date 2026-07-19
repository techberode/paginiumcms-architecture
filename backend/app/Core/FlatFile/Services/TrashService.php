<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Support\JsonHelper;

/**
 * Správa koša (soft-delete) – zoznam a obnova súborov.
 */
class TrashService
{
    private string $trashPath;

    public function __construct(
        private FileReaderInterface $reader
    ) {
        $this->trashPath = rtrim($this->reader->getBasePath(), '/') . '/trash';
    }

    /**
     * @return list<array{id: string, originalPath: string, deletedAt: string, filename: string, size: int}>
     */
    public function listItems(): array
    {
        if (!is_dir($this->trashPath)) {
            return [];
        }

        $items = [];
        foreach (scandir($this->trashPath) ?: [] as $entry) {
            if (!str_ends_with($entry, '.meta.json')) {
                continue;
            }

            $metaPath = $this->trashPath . '/' . $entry;
            if (!is_file($metaPath)) {
                continue;
            }

            $rawMeta = file_get_contents($metaPath);
            if ($rawMeta === false || $rawMeta === '') {
                continue;
            }

            $meta = JsonHelper::decode($rawMeta);
            if ($meta === []) {
                continue;
            }

            $trashFilename = (string) ($meta['trashFilename'] ?? '');
            $trashFilePath = $this->trashPath . '/' . $trashFilename;
            $size = is_file($trashFilePath) ? (int) filesize($trashFilePath) : 0;

            $items[] = [
                'id' => (string) ($meta['id'] ?? pathinfo($entry, PATHINFO_FILENAME)),
                'originalPath' => (string) ($meta['originalPath'] ?? ''),
                'deletedAt' => (string) ($meta['deletedAt'] ?? ''),
                'filename' => $trashFilename,
                'size' => $size,
            ];
        }

        usort($items, static fn (array $a, array $b): int => strcmp($b['deletedAt'], $a['deletedAt']));

        return $items;
    }

    public function restore(string $id): string
    {
        $metaPath = $this->findMetaPath($id);
        if ($metaPath === null) {
            throw new FlatFileException('Položka v koši neexistuje: ' . $id);
        }

        $meta = JsonHelper::decode((string) file_get_contents($metaPath));
        $originalPath = (string) ($meta['originalPath'] ?? '');
        $trashFilename = (string) ($meta['trashFilename'] ?? '');

        if ($originalPath === '' || $trashFilename === '') {
            throw new FlatFileException('Neplatné metadáta koša pre: ' . $id);
        }

        $trashFile = $this->trashPath . '/' . $trashFilename;
        if (!is_file($trashFile)) {
            throw new FlatFileException('Súbor v koši neexistuje: ' . $trashFilename);
        }

        $destination = rtrim($this->reader->getBasePath(), '/') . '/' . ltrim($originalPath, '/');
        $destinationDir = dirname($destination);
        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }

        if (is_file($destination)) {
            throw new FlatFileException('Cieľová cesta už existuje: ' . $originalPath);
        }

        if (!rename($trashFile, $destination)) {
            throw new FlatFileException('Nepodarilo sa obnoviť súbor: ' . $originalPath);
        }

        @unlink($metaPath);

        return $originalPath;
    }

    public function purge(string $id): void
    {
        $metaPath = $this->findMetaPath($id);
        if ($metaPath === null) {
            throw new FlatFileException('Položka v koši neexistuje: ' . $id);
        }

        $meta = JsonHelper::decode((string) file_get_contents($metaPath));
        $trashFilename = (string) ($meta['trashFilename'] ?? '');

        if ($trashFilename !== '') {
            $trashFile = $this->trashPath . '/' . $trashFilename;
            if (is_file($trashFile)) {
                @unlink($trashFile);
            }
        }

        @unlink($metaPath);
    }

    public function purgeAll(): int
    {
        if (!is_dir($this->trashPath)) {
            return 0;
        }

        $removed = 0;
        foreach ($this->listItems() as $item) {
            try {
                $this->purge((string) $item['id']);
                ++$removed;
            } catch (FlatFileException) {
                continue;
            }
        }

        return $removed;
    }

    /**
     * @param list<string> $ids
     * @return array{filename: string, path: string, size: int, count: int}
     */
    public function backupItems(array $ids): array
    {
        if ($ids === []) {
            throw new FlatFileException('Vyžaduje sa aspoň jedna položka koša');
        }

        $backupDirectory = $this->backupDirectory();
        if (!is_dir($backupDirectory) && !mkdir($backupDirectory, 0755, true) && !is_dir($backupDirectory)) {
            throw new FlatFileException('Nepodarilo sa vytvoriť adresár zálohy');
        }

        $timestamp = date('Y-m-d_H-i-s');
        $filename = 'trash-backup_' . $timestamp . '.zip';
        $fullPath = rtrim($backupDirectory, '/') . '/' . $filename;

        $zip = new \ZipArchive();
        if ($zip->open($fullPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new FlatFileException('Nepodarilo sa vytvoriť ZIP archív koša');
        }

        $count = 0;
        foreach ($ids as $id) {
            $metaPath = $this->findMetaPath($id);
            if ($metaPath === null) {
                continue;
            }

            $meta = JsonHelper::decode((string) file_get_contents($metaPath));
            $trashFilename = (string) ($meta['trashFilename'] ?? '');
            if ($trashFilename === '') {
                continue;
            }

            $trashFile = $this->trashPath . '/' . $trashFilename;
            if (!is_file($trashFile)) {
                continue;
            }

            $zip->addFile($trashFile, 'files/' . $trashFilename);
            $zip->addFile($metaPath, 'meta/' . basename($metaPath));
            ++$count;
        }

        $zip->close();

        if ($count === 0 || !is_file($fullPath)) {
            @unlink($fullPath);
            throw new FlatFileException('Nepodarilo sa zálohovať vybrané položky koša');
        }

        return [
            'filename' => $filename,
            'path' => $fullPath,
            'size' => (int) filesize($fullPath),
            'count' => $count,
        ];
    }

    private function backupDirectory(): string
    {
        $storageRoot = dirname($this->reader->getBasePath(), 2);

        return $storageRoot . '/backups/trash-exports';
    }

    public function resolveBackupPath(string $filename): ?string
    {
        $safeName = basename($filename);
        if ($safeName !== $filename || !str_starts_with($safeName, 'trash-backup_') || !str_ends_with($safeName, '.zip')) {
            return null;
        }

        $path = $this->backupDirectory() . '/' . $safeName;

        return is_file($path) ? $path : null;
    }

    private function findMetaPath(string $id): ?string
    {
        if (!is_dir($this->trashPath)) {
            return null;
        }

        foreach (scandir($this->trashPath) ?: [] as $entry) {
            if (!str_ends_with($entry, '.meta.json')) {
                continue;
            }

            $metaPath = $this->trashPath . '/' . $entry;
            if (!is_file($metaPath)) {
                continue;
            }

            $rawMeta = file_get_contents($metaPath);
            if ($rawMeta === false || $rawMeta === '') {
                continue;
            }

            $meta = JsonHelper::decode($rawMeta);
            if (($meta['id'] ?? '') === $id) {
                return $metaPath;
            }
        }

        return null;
    }
}
