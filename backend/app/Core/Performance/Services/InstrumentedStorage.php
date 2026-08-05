<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Performance\Services;

use PaginiumCMS\Core\Performance\PerformanceContext;
use PaginiumCMS\Core\Storage\Contracts\StorageInterface;
use PaginiumCMS\Core\Storage\Exception\StorageException;

/**
 * Storage decorator that counts reads/writes for Performance Guard (Iteration 71).
 */
final class InstrumentedStorage implements StorageInterface
{
    public function __construct(
        private StorageInterface $inner,
        private PerformanceContext $context
    ) {
    }

    public function read(string $logicalPath): string
    {
        $this->context->recordStorageRead();

        return $this->inner->read($logicalPath);
    }

    public function write(string $logicalPath, string $content, bool $createBackup = false): void
    {
        $this->context->recordStorageWrite();
        $this->inner->write($logicalPath, $content, $createBackup);
    }

    public function exists(string $logicalPath): bool
    {
        $this->context->recordStorageRead();

        return $this->inner->exists($logicalPath);
    }

    public function delete(string $logicalPath, bool $moveToTrash = true): void
    {
        $this->context->recordStorageWrite();
        $this->inner->delete($logicalPath, $moveToTrash);
    }

    public function list(string $logicalDirectory, string $pattern = '*'): array
    {
        $this->context->recordStorageRead();

        return $this->inner->list($logicalDirectory, $pattern);
    }

    public function getBasePath(): string
    {
        return $this->inner->getBasePath();
    }

    public function resolveAbsolutePath(string $logicalPath): string
    {
        try {
            return $this->inner->resolveAbsolutePath($logicalPath);
        } catch (StorageException $e) {
            throw $e;
        }
    }
}
