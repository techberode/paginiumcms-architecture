<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Contracts;

/**
 * Binary object storage for media files (Iteration 72).
 * Registry and sidecar metadata remain flat-file SSOT in MediaRepository.
 */
interface MediaStorageDriverInterface
{
    public function put(string $relativePath, string $binary): void;

    public function read(string $relativePath): string;

    public function delete(string $relativePath): void;

    public function exists(string $relativePath): bool;

    public function checksum(string $relativePath): string;

    public function publicUrl(string $relativePath): string;

    /**
     * @return array{ok: bool, driver: string, latencyMs: int, message: string}
     */
    public function health(): array;
}
