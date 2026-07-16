<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Contracts;

use PaginiumCMS\Core\FlatFile\Models\MediaFile;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;

interface MediaRepositoryInterface
{
    /**
     * @return array<int, MediaFile>
 * @param array<int|string, mixed> $filters
 */public function findAll(array $filters = []): array;

    public function findByPath(string $path): ?MediaFile;

    /**
     * @param resource|string $contents
     */
    public function saveUpload(string $originalName, $contents, string $mimeType, string $altText = ''): MediaFile;

    /**
     * @throws FlatFileException
     */
    public function delete(string $path): void;

    /**
     * @throws FlatFileException
     */
    public function update(MediaFile $file): void;
}
