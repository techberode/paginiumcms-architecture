<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Services;

use function utf8_normalize;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Exception\InvalidPathException;

/**
 * Služba na zápis súborov do FlatFile úložiska.
 */
class FileWriter implements FileWriterInterface
{
    private FileValidator $validator;
    private string $trashPath;

    public function __construct(FileValidator $validator)
    {
        $this->validator = $validator;
        $this->trashPath = $this->validator->getAbsolutePath('trash');
    }

    public function write(string $relativePath, string $content, bool $createBackup = true): void
    {
        // Normalizácia UTF-8 pred zápisom
        $content = utf8_normalize($content);

    
        $absolutePath = $this->validator->getAbsolutePath($relativePath);
        $isVirtual = strpos($absolutePath, 'vfs://') === 0;

        // Vytvorenie záložnej kópie
        if ($createBackup && file_exists($absolutePath)) {
            $this->createBackup($relativePath);
        }

        // Vytvorenie adresárov, ak neexistujú
        $directory = dirname($absolutePath);
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new FlatFileException(sprintf('Nepodarilo sa vytvoriť adresár: %s', $directory));
            }
        }

        // Zápis súboru - pre virtuálny systém použijeme bez zámku
        if ($isVirtual) {
            $result = file_put_contents($absolutePath, $content);
        } else {
            $result = file_put_contents($absolutePath, $content, LOCK_EX);
        }

        if ($result === false) {
            throw new FlatFileException(sprintf(
                'Nepodarilo sa zapísať súbor: %s (cesta: %s)',
                                                $relativePath,
                                                $absolutePath
            ));
        }

        // Nastavenie oprávnení (ak nie je v stream protokole)
        if (!$isVirtual) {
            chmod($absolutePath, 0644);
        }
    }

    public function writeBinary(string $relativePath, string $content, bool $createBackup = true): void
    {
        $absolutePath = $this->validator->getAbsolutePath($relativePath);
        $isVirtual = strpos($absolutePath, 'vfs://') === 0;

        if ($createBackup && file_exists($absolutePath)) {
            $this->createBackup($relativePath);
        }

        $directory = dirname($absolutePath);
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new FlatFileException(sprintf('Nepodarilo sa vytvoriť adresár: %s', $directory));
            }
        }

        if ($isVirtual) {
            $result = file_put_contents($absolutePath, $content);
        } else {
            $result = file_put_contents($absolutePath, $content, LOCK_EX);
        }

        if ($result === false) {
            throw new FlatFileException(sprintf(
                'Nepodarilo sa zapísať binárny súbor: %s (cesta: %s)',
                $relativePath,
                $absolutePath
            ));
        }

        if (!$isVirtual) {
            chmod($absolutePath, 0644);
        }
    }

    public function delete(string $relativePath, bool $moveToTrash = true): void
    {
        $absolutePath = $this->validator->getAbsolutePath($relativePath);

        if (!file_exists($absolutePath)) {
            return;
        }

        if ($moveToTrash) {
            $this->moveToTrash($relativePath);
        } else {
            if (!unlink($absolutePath)) {
                throw new FlatFileException(sprintf('Nepodarilo sa vymazať súbor: %s', $relativePath));
            }
        }
    }

    public function createDirectory(string $relativePath, int $permissions = 0755): void
    {
        $absolutePath = $this->validator->getAbsolutePath($relativePath);

        if (is_dir($absolutePath)) {
            return;
        }

        if (!mkdir($absolutePath, $permissions, true) && !is_dir($absolutePath)) {
            throw new FlatFileException(sprintf('Nepodarilo sa vytvoriť adresár: %s', $relativePath));
        }
    }

    public function copy(string $source, string $destination): void
    {
        $sourceAbsolute = $this->validator->getAbsolutePath($source);
        $destinationAbsolute = $this->validator->getAbsolutePath($destination);

        if (!file_exists($sourceAbsolute)) {
            throw new FlatFileException(sprintf('Zdroj neexistuje: %s', $source));
        }

        $destinationDir = dirname($destinationAbsolute);
        if (!is_dir($destinationDir)) {
            if (!mkdir($destinationDir, 0755, true) && !is_dir($destinationDir)) {
                throw new FlatFileException(sprintf('Nepodarilo sa vytvoriť cieľový adresár: %s', $destinationDir));
            }
        }

        if (!copy($sourceAbsolute, $destinationAbsolute)) {
            throw new FlatFileException(sprintf('Nepodarilo sa skopírovať: %s -> %s', $source, $destination));
        }
    }

    public function move(string $source, string $destination): void
    {
        $sourceAbsolute = $this->validator->getAbsolutePath($source);
        $destinationAbsolute = $this->validator->getAbsolutePath($destination);

        if (!file_exists($sourceAbsolute)) {
            throw new FlatFileException(sprintf('Zdroj neexistuje: %s', $source));
        }

        $destinationDir = dirname($destinationAbsolute);
        if (!is_dir($destinationDir)) {
            if (!mkdir($destinationDir, 0755, true) && !is_dir($destinationDir)) {
                throw new FlatFileException(sprintf('Nepodarilo sa vytvoriť cieľový adresár: %s', $destinationDir));
            }
        }

        if (!rename($sourceAbsolute, $destinationAbsolute)) {
            throw new FlatFileException(sprintf('Nepodarilo sa presunúť: %s -> %s', $source, $destination));
        }
    }

    private function createBackup(string $relativePath): void
    {
        $absolutePath = $this->validator->getAbsolutePath($relativePath);

        if (!file_exists($absolutePath)) {
            return;
        }

        $backupPath = $absolutePath . '.backup.' . date('Ymd_His');

        if (!copy($absolutePath, $backupPath)) {
            throw new FlatFileException(sprintf('Nepodarilo sa vytvoriť záložnú kópiu: %s', $relativePath));
        }

        $this->pruneBackups($absolutePath, 5);
    }

    private function pruneBackups(string $absolutePath, int $keep): void
    {
        $dir = dirname($absolutePath);
        $base = basename($absolutePath);
        $pattern = $dir . DIRECTORY_SEPARATOR . $base . '.backup.*';
        $backups = glob($pattern) ?: [];

        if (count($backups) <= $keep) {
            return;
        }

        usort($backups, static fn (string $a, string $b): int => (int) filemtime($b) <=> (int) filemtime($a));

        foreach (array_slice($backups, $keep) as $oldBackup) {
            if (is_file($oldBackup)) {
                @unlink($oldBackup);
            }
        }
    }

    public function getBasePath(): string
    {
        return $this->validator->getBasePath();
    }

    private function moveToTrash(string $relativePath): void
    {
        $absolutePath = $this->validator->getAbsolutePath($relativePath);

        if (!is_dir($this->trashPath)) {
            mkdir($this->trashPath, 0755, true);
        }

        $filename = basename($relativePath);
        $timestamp = date('Y-m-d_H-i-s');
        $trashFilename = $timestamp . '_' . $filename;
        $trashPath = $this->trashPath . '/' . $trashFilename;
        $metaId = $timestamp . '_' . pathinfo($filename, PATHINFO_FILENAME);

        if (!rename($absolutePath, $trashPath)) {
            throw new FlatFileException(sprintf('Nepodarilo sa presunúť súbor do koša: %s', $relativePath));
        }

        $meta = [
            'id' => $metaId,
            'originalPath' => $relativePath,
            'deletedAt' => date('c'),
            'trashFilename' => $trashFilename,
        ];

        file_put_contents(
            $trashPath . '.meta.json',
            json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
}
