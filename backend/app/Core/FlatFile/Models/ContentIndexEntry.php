<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Models;

/**
 * Záznam v flat-file content indexe (Iterácia 19).
 *
 * @phpstan-type ContentIndexEntryArray array{
 *     slug: string,
 *     type: string,
 *     title: string,
 *     status: string,
 *     author: string,
 *     path: string,
 *     excerpt: string,
 *     tags: list<string>,
 *     updatedAt: string,
 *     createdAt: string
 * }
 */
final class ContentIndexEntry
{
    /**
     * @param list<string> $tags
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $type,
        public readonly string $title,
        public readonly string $status,
        public readonly string $author,
        public readonly string $path,
        public readonly string $excerpt,
        public readonly array $tags,
        public readonly string $updatedAt,
        public readonly string $createdAt
    ) {
    }

    public static function fromContent(Content $content, string $type, string $excerpt = ''): self
    {
        $frontMatter = $content->getFrontMatter();
        $modifiedAt = $content->getModifiedAt() > 0
            ? date('c', $content->getModifiedAt())
            : date('c');

        $tags = [];
        if ($content instanceof Article) {
            $tags = array_values(array_map('strval', $content->getTags()));
        }

        if ($excerpt === '' && $content instanceof Article) {
            $excerpt = $content->getExcerpt(160);
        }

        $createdAt = is_string($frontMatter['createdAt'] ?? null) ? $frontMatter['createdAt'] : $modifiedAt;
        if ($content instanceof Article && is_string($frontMatter['date'] ?? null)) {
            $createdAt = $frontMatter['date'];
        }

        return new self(
            slug: $content->getSlug(),
            type: $type,
            title: $content->getTitle(),
            status: $content->getStatus(),
            author: $content->getAuthor(),
            path: $content->getPath(),
            excerpt: $excerpt,
            tags: $tags,
            updatedAt: is_string($frontMatter['updatedAt'] ?? null) ? $frontMatter['updatedAt'] : $modifiedAt,
            createdAt: $createdAt,
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $rawTags = $data['tags'] ?? [];
        $tags = [];
        if (is_array($rawTags)) {
            foreach ($rawTags as $tag) {
                if (is_string($tag) || is_int($tag)) {
                    $tags[] = (string) $tag;
                }
            }
        }

        return new self(
            slug: (string) ($data['slug'] ?? ''),
            type: (string) ($data['type'] ?? 'page'),
            title: (string) ($data['title'] ?? ''),
            status: (string) ($data['status'] ?? 'draft'),
            author: (string) ($data['author'] ?? ''),
            path: (string) ($data['path'] ?? ''),
            excerpt: (string) ($data['excerpt'] ?? ''),
            tags: $tags,
            updatedAt: (string) ($data['updatedAt'] ?? date('c')),
            createdAt: (string) ($data['createdAt'] ?? date('c')),
        );
    }

    /**
     * @return ContentIndexEntryArray
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'type' => $this->type,
            'title' => $this->title,
            'status' => $this->status,
            'author' => $this->author,
            'path' => $this->path,
            'excerpt' => $this->excerpt,
            'tags' => $this->tags,
            'updatedAt' => $this->updatedAt,
            'createdAt' => $this->createdAt,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchResult(): array
    {
        return [
            'slug' => $this->slug,
            'type' => $this->type,
            'title' => $this->title,
            'status' => $this->status,
            'excerpt' => $this->excerpt,
            'tags' => $this->tags,
            'updatedAt' => $this->updatedAt,
            'path' => $this->path,
        ];
    }
}
