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
            $meta = JsonHelper::decode((string) file_get_contents($metaPath));
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
            $meta = JsonHelper::decode((string) file_get_contents($metaPath));
            if (($meta['id'] ?? '') === $id) {
                return $metaPath;
            }
        }

        return null;
    }
}
