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
        $started = hrtime(true);

        try {
            return $this->inner->read($logicalPath);
        } finally {
            $this->context->recordStorageReadDuration(hrtime(true) - $started);
        }
    }

    public function write(string $logicalPath, string $content, bool $createBackup = false): void
    {
        $started = hrtime(true);

        try {
            $this->inner->write($logicalPath, $content, $createBackup);
        } finally {
            $this->context->recordStorageWriteDuration(hrtime(true) - $started);
        }
    }

    public function exists(string $logicalPath): bool
    {
        $started = hrtime(true);

        try {
            return $this->inner->exists($logicalPath);
        } finally {
            $this->context->recordStorageReadDuration(hrtime(true) - $started);
        }
    }

    public function delete(string $logicalPath, bool $moveToTrash = true): void
    {
        $started = hrtime(true);

        try {
            $this->inner->delete($logicalPath, $moveToTrash);
        } finally {
            $this->context->recordStorageWriteDuration(hrtime(true) - $started);
        }
    }

    public function list(string $logicalDirectory, string $pattern = '*'): array
    {
        $started = hrtime(true);

        try {
            return $this->inner->list($logicalDirectory, $pattern);
        } finally {
            $this->context->recordStorageReadDuration(hrtime(true) - $started);
        }
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
