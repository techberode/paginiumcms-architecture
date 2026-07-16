<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Models;

use DateTimeImmutable;
use JsonSerializable;

/**
 * Abstraktný model pre obsah.
 *
 * Reprezentuje spoločné vlastnosti pre stránky a články.
 */
abstract class Content implements JsonSerializable
{
    protected string $path = '';
    /** @var array<int|string, mixed> */
    protected array $frontMatter = [];
    protected string $content = '';
    protected string $html = '';
    protected int $size = 0;
    protected int $modifiedAt = 0;

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getFrontMatter(): array
    {
        return $this->frontMatter;
    }

    /**
     * @param array<int|string, mixed> $frontMatter
     */
    public function setFrontMatter(array $frontMatter): self
    {
        $this->frontMatter = $frontMatter;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getHtml(): string
    {
        return $this->html;
    }

    public function setHtml(string $html): self
    {
        $this->html = $html;
        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;
        return $this;
    }

    public function getModifiedAt(): int
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(int $modifiedAt): self
    {
        $this->modifiedAt = $modifiedAt;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->frontMatter['title'] ?? '';
    }

    public function setTitle(string $title): self
    {
        $this->frontMatter['title'] = $title;
        return $this;
    }

    public function getSlug(): string
    {
        return $this->frontMatter['slug'] ?? '';
    }

    public function setSlug(string $slug): self
    {
        $this->frontMatter['slug'] = $slug;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->frontMatter['description'] ?? '';
    }

    public function setDescription(string $description): self
    {
        $this->frontMatter['description'] = $description;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->frontMatter['status'] ?? 'draft';
    }

    public function setStatus(string $status): self
    {
        $this->frontMatter['status'] = $status;
        return $this;
    }

    public function getAuthor(): string
    {
        return $this->frontMatter['author'] ?? '';
    }

    public function setAuthor(string $author): self
    {
        $this->frontMatter['author'] = $author;
        return $this;
    }

    public function getDate(): ?DateTimeImmutable
    {
        if (empty($this->frontMatter['date'])) {
            return null;
        }

        try {
            return new DateTimeImmutable($this->frontMatter['date']);
        } catch (\Exception) {
            return null;
        }
    }

    public function setDate(DateTimeImmutable $date): self
    {
        $this->frontMatter['date'] = $date->format('Y-m-d H:i:s');
        return $this;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getTags(): array
    {
        return $this->frontMatter['tags'] ?? [];
    }

    /**
     * @param array<int|string, mixed> $tags
     */
    public function setTags(array $tags): self
    {
        $this->frontMatter['tags'] = $tags;
        return $this;
    }

    public function isPublished(): bool
    {
        return $this->getStatus() === 'published';
    }

    public function isDraft(): bool
    {
        return $this->getStatus() === 'draft';
    }

    public function isArchived(): bool
    {
        return $this->getStatus() === 'archived';
    }

    /**
     * {@inheritDoc}
 * @return array<int|string, mixed>
 */public function jsonSerialize(): array
    {
        return [
            'path' => $this->path,
            'frontMatter' => $this->frontMatter,
            'content' => $this->content,
            'html' => $this->html,
            'size' => $this->size,
            'modifiedAt' => $this->modifiedAt,
        ];
    }
}
