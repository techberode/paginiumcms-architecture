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
     *     previewableMimeTypes: list<string>
     * }
     */
    public function formatsPayload(): array;
}
