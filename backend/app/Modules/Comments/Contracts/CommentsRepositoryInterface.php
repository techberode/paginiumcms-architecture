<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Comments\Contracts;

use PaginiumCMS\Modules\Comments\Models\Comment;

interface CommentsRepositoryInterface
{
    /**
     * @param array<int|string, mixed> $filters
     * @return list<Comment>
 * @return array<int|string, mixed>
 */public function findAll(array $filters = []): array;

    public function findById(string $id): ?Comment;

    public function save(Comment $comment): void;

    public function update(Comment $comment): void;

    public function delete(string $id): void;
}
