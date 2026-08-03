<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Storage\Drivers;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Exception\FileNotFoundException;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Exception\InvalidPathException;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\Storage\Contracts\StorageInterface;
use PaginiumCMS\Core\Storage\Exception\StorageException;

use function utf8_normalize;

/**
 * Local flat-file storage driver — delegates to existing safe I/O services.
 */
final class LocalFlatFileStorage implements StorageInterface
{
    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private FileValidator $validator,
    ) {
    }

    public function read(string $logicalPath): string
    {
        try {
            $this->assertWithinBase($logicalPath);

            return $this->reader->read($logicalPath);
        } catch (FileNotFoundException $e) {
            throw new StorageException($e->getMessage(), 0, $e);
        } catch (InvalidPathException $e) {
            throw new StorageException($e->getMessage(), 0, $e);
        }
    }

    public function write(string $logicalPath, string $content, bool $createBackup = false): void
    {
        $content = utf8_normalize($content);

        try {
            $this->assertWithinBase($logicalPath);

            if ($createBackup) {
                $this->writer->write($logicalPath, $content, true);

                return;
            }

            $absolutePath = $this->validator->getAbsolutePath($logicalPath);
            $isVirtual = str_starts_with($absolutePath, 'vfs://');

            $directory = dirname($absolutePath);
            if (!is_dir($directory)) {
                $relativeDir = dirname($logicalPath);
                if ($relativeDir !== '.' && $relativeDir !== '') {
                    $this->writer->createDirectory($relativeDir);
                } elseif ($logicalPath !== '') {
                    $this->writer->createDirectory('');
                }
            }

            if ($isVirtual) {
                $result = @file_put_contents($absolutePath, $content);
            } else {
                $result = $this->atomicWrite($absolutePath, $content);
            }

            if ($result === false) {
                throw new StorageException(sprintf('Failed to write document: %s', $logicalPath));
            }

            if (!$isVirtual) {
                @chmod($absolutePath, 0664);
            }
        } catch (FlatFileException|InvalidPathException $e) {
            throw new StorageException($e->getMessage(), 0, $e);
        }
    }

    public function exists(string $logicalPath): bool
    {
        try {
            $this->assertWithinBase($logicalPath);

            return $this->reader->exists($logicalPath);
        } catch (InvalidPathException $e) {
            throw new StorageException($e->getMessage(), 0, $e);
        }
    }

    public function delete(string $logicalPath, bool $moveToTrash = true): void
    {
        try {
            $this->assertWithinBase($logicalPath);
            $this->writer->delete($logicalPath, $moveToTrash);
        } catch (FlatFileException|InvalidPathException $e) {
            throw new StorageException($e->getMessage(), 0, $e);
        }
    }

    public function list(string $logicalDirectory, string $pattern = '*'): array
    {
        try {
            $this->assertWithinBase($logicalDirectory);

            /** @var list<string> $files */
            $files = $this->reader->listFiles($logicalDirectory, $pattern);

            return $files;
        } catch (InvalidPathException $e) {
            throw new StorageException($e->getMessage(), 0, $e);
        }
    }

    public function getBasePath(): string
    {
        return $this->reader->getBasePath();
    }

    public function resolveAbsolutePath(string $logicalPath): string
    {
        try {
            $this->assertWithinBase($logicalPath);

            return $this->validator->getAbsolutePath($logicalPath);
        } catch (InvalidPathException $e) {
            throw new StorageException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @return int|false Bytes written or false on failure.
     */
    private function atomicWrite(string $absolutePath, string $content): int|false
    {
        $directory = dirname($absolutePath);
        $tempPath = $directory . '/.' . basename($absolutePath) . '.' . bin2hex(random_bytes(8)) . '.tmp';

        $handle = @fopen($tempPath, 'cb');
        if ($handle === false) {
            return false;
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return false;
            }

            $written = fwrite($handle, $content);
            if ($written === false) {
                return false;
            }

            fflush($handle);

            if (function_exists('fsync')) {
                fsync($handle);
            }

            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }

        if (!rename($tempPath, $absolutePath)) {
            @unlink($tempPath);

            return false;
        }

        return strlen($content);
    }

    /**
     * Rejects paths that escape the configured base via symlinks.
     *
     * @throws InvalidPathException
     */
    private function assertWithinBase(string $logicalPath): void
    {
        $this->validator->validatePath($logicalPath);

        if ($logicalPath === '' || $logicalPath === '.') {
            return;
        }

        $absolutePath = $this->validator->getAbsolutePath($logicalPath);
        if (str_starts_with($absolutePath, 'vfs://')) {
            return;
        }

        $baseReal = realpath($this->validator->getBasePath());
        if ($baseReal === false) {
            throw new InvalidPathException($logicalPath, 'Storage root is unavailable');
        }

        $parent = dirname($absolutePath);
        $parentReal = realpath($parent);
        if ($parentReal === false) {
            if (!str_starts_with($parent, $baseReal . DIRECTORY_SEPARATOR) && $parent !== $baseReal) {
                throw new InvalidPathException($logicalPath, 'Path escapes storage root');
            }

            return;
        }

        if ($parentReal !== $baseReal && !str_starts_with($parentReal, $baseReal . DIRECTORY_SEPARATOR)) {
            throw new InvalidPathException($logicalPath, 'Path escapes storage root (symlink)');
        }
    }
}
