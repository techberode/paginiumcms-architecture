<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Gallery\Contracts;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Modules\Gallery\Models\GalleryItem;

interface GalleryRepositoryInterface
{
    /**
     * @return list<GalleryItem>
     */
    public function findAllOrdered(): array;

    /**
     * @return list<GalleryItem>
     */
    public function findPublishedOrdered(): array;

    public function findById(string $id): ?GalleryItem;

    /**
     * @param array<string, mixed> $payload
     *
     * @throws FlatFileException
     */
    public function create(array $payload): GalleryItem;

    /**
     * @param array<string, mixed> $payload
     *
     * @throws FlatFileException
     */
    public function update(string $id, array $payload): GalleryItem;

    /**
     * @param list<string> $ids
     *
     * @throws FlatFileException
     */
    public function reorder(array $ids): void;

    /**
     * @throws FlatFileException
     */
    public function delete(string $id): void;
}
