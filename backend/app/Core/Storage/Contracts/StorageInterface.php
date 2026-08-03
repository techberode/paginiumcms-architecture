<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Storage\Contracts;

use PaginiumCMS\Core\Storage\Exception\StorageException;

/**
 * Logical document storage contract (Iteration 68 — Hybrid Engine foundation).
 *
 * Paths are relative to the storage root (e.g. `data/settings.json`), never raw
 * filesystem paths. Implementations must enforce allow-listed roots and reject
 * traversal, symlink escapes, and null-byte paths.
 */
interface StorageInterface
{
    /**
     * @throws StorageException
     */
    public function read(string $logicalPath): string;

    /**
     * Persists content using atomic temp write → fsync → rename when supported.
     *
     * @throws StorageException
     */
    public function write(string $logicalPath, string $content, bool $createBackup = false): void;

    public function exists(string $logicalPath): bool;

    /**
     * @throws StorageException
     */
    public function delete(string $logicalPath, bool $moveToTrash = true): void;

    /**
     * @return list<string> Relative file paths under the given directory.
     *
     * @throws StorageException
     */
    public function list(string $logicalDirectory, string $pattern = '*'): array;

    public function getBasePath(): string;

    /**
     * Resolves a validated logical path to an absolute filesystem path.
     *
     * @throws StorageException
     */
    public function resolveAbsolutePath(string $logicalPath): string;
}
