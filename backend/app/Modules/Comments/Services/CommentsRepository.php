<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Comments\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Modules\Comments\Contracts\CommentsRepositoryInterface;
use PaginiumCMS\Modules\Comments\Models\Comment;

class CommentsRepository implements CommentsRepositoryInterface
{
    private const REGISTRY = 'data/comments.json';

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer
    ) {
    }

    public function findAll(array $filters = []): array
    {
        $comments = [];
        foreach ($this->loadRegistry() as $entry) {
            $comment = Comment::fromArray($entry);
            if ($this->matchesFilters($comment, $filters)) {
                $comments[] = $comment;
            }
        }

        usort($comments, fn (Comment $a, Comment $b) => strcmp($b->getCreatedAt(), $a->getCreatedAt()));

        return $comments;
    }

    public function findById(string $id): ?Comment
    {
        foreach ($this->loadRegistry() as $entry) {
            if (($entry['id'] ?? '') === $id) {
                return Comment::fromArray($entry);
            }
        }

        return null;
    }

    public function save(Comment $comment): void
    {
        $registry = $this->loadRegistry();
        $registry[] = $comment->jsonSerialize();
        $this->writeRegistry($registry);
    }

    public function update(Comment $comment): void
    {
        $registry = $this->loadRegistry();
        $updated = false;

        foreach ($registry as $index => $entry) {
            if (($entry['id'] ?? '') !== $comment->getId()) {
                continue;
            }

            $registry[$index] = $comment->jsonSerialize();
            $updated = true;
            break;
        }

        if (!$updated) {
            throw new FlatFileException('Comment not found');
        }

        $this->writeRegistry($registry);
    }

    public function delete(string $id): void
    {
        $registry = $this->loadRegistry();
        $found = false;

        foreach ($registry as $index => $entry) {
            if (($entry['id'] ?? '') !== $id) {
                continue;
            }

            unset($registry[$index]);
            $found = true;
            break;
        }

        if (!$found) {
            throw new FlatFileException('Comment not found');
        }

        $this->writeRegistry(array_values($registry));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadRegistry(): array
    {
        if (!$this->reader->exists(self::REGISTRY)) {
            return [];
        }

        try {
            $content = $this->reader->read(self::REGISTRY);
            $data = json_decode($content, true);

            return is_array($data) ? $data : [];
        } catch (FlatFileException) {
            return [];
        }
    }

    /**
     * @param array<int, array<string, mixed>> $registry
     */
    private function writeRegistry(array $registry): void
    {
        $json = json_encode(array_values($registry), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new FlatFileException('Failed to serialize comments registry');
        }

        $this->writer->write(self::REGISTRY, $json, true);
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function matchesFilters(Comment $comment, array $filters): bool
    {
        if (isset($filters['articleSlug']) && $comment->getArticleSlug() !== $filters['articleSlug']) {
            return false;
        }

        if (isset($filters['status']) && $comment->getStatus() !== $filters['status']) {
            return false;
        }

        return true;
    }
}
