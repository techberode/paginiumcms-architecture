<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Contracts;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Models\MediaFile;

interface MediaRepositoryInterface
{
    /**
     * @param array<string, mixed> $filters
     * @return array<int, MediaFile>
     */
    public function findAll(array $filters = []): array;

    public function findByPath(string $path): ?MediaFile;

    /**
     * @throws FlatFileException
     */
    public function readBinary(string $path): string;

    /**
     * @param resource|string $contents
     */
    public function saveUpload(
        string $originalName,
        $contents,
        string $mimeType,
        string $altText = '',
        string $folder = ''
    ): MediaFile;

    /**
     * @throws FlatFileException
     */
    public function delete(string $path): void;

    /**
     * @param list<string> $paths
     * @return int Number of deleted items
     */
    public function bulkDelete(array $paths): int;

    /**
     * @throws FlatFileException
     */
    public function update(MediaFile $file): void;

    /**
     * @return list<string> Folder paths (empty string = root)
     */
    public function listFolders(): array;

    /**
     * @throws FlatFileException
     */
    public function createFolder(string $folder): void;

    /**
     * @return list<string>
     */
    public function resolveAllowedMimeTypes(): array;

    /**
     * @return array{
     *     mimeTypes: list<string>,
     *     extensions: list<string>,
     *     accept: string,
     *     previewableMimeTypes: list<string>,
     *     imageOptimization: array{
     *         available: bool,
     *         jpeg: bool,
     *         png: bool,
     *         webp: bool
     *     }
     * }
     */
    public function formatsPayload(): array;

    /**
     * @return array{width: int, height: int, mimeType: string, sizeBytes: int}
     */
    public function inspectRaster(string $path): array;

    /**
     * Re-encode raster image bytes; optional proportional downscale.
     *
     * @return array{
     *     media: array<string, mixed>,
     *     beforeBytes: int,
     *     afterBytes: int,
     *     savedBytes: int,
     *     savedPercent: float,
     *     beforeWidth: int,
     *     beforeHeight: int,
     *     width: int,
     *     height: int
     * }
     */
    public function optimizeRaster(string $path, ?int $targetWidth = null, ?int $targetHeight = null): array;

    /**
     * @return array{
     *     previewToken: string,
     *     beforeBytes: int,
     *     afterBytes: int,
     *     savedBytes: int,
     *     savedPercent: float,
     *     beforeWidth: int,
     *     beforeHeight: int,
     *     width: int,
     *     height: int
     * }
     */
    public function previewOptimizeRaster(
        string $path,
        string $ownerUserId,
        ?int $targetWidth = null,
        ?int $targetHeight = null,
    ): array;

    /**
     * @return array{
     *     media: array<string, mixed>,
     *     beforeBytes: int,
     *     afterBytes: int,
     *     savedBytes: int,
     *     savedPercent: float,
     *     beforeWidth: int,
     *     beforeHeight: int,
     *     width: int,
     *     height: int
     * }
     */
    public function applyOptimizePreview(string $path, string $previewToken, string $ownerUserId): array;

    /**
     * @return array{mimeType: string, binary: string, mediaPath: string}|null
     */
    public function readOptimizePreview(string $previewToken, string $ownerUserId): ?array;
}
