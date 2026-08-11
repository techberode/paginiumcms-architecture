<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Comments\Models;

use JsonSerializable;

class Comment implements JsonSerializable
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_QUARANTINE = 'quarantine';

    private string $id;
    private string $articleSlug;
    private string $author;
    private string $email = '';
    private string $content;
    private string $status = self::STATUS_PENDING;
    private string $createdAt;
    private ?string $approvedAt = null;
    private bool $isRead = false;
    private bool $isArchived = false;

    public function __construct(string $articleSlug, string $author, string $content)
    {
        $this->id = uniqid('comment_', true);
        $this->articleSlug = $articleSlug;
        $this->author = $author;
        $this->content = $content;
        $this->createdAt = date('c');
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getArticleSlug(): string
    {
        return $this->articleSlug;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        if ($status === self::STATUS_APPROVED) {
            $this->approvedAt = date('c');
        }

        return $this;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getApprovedAt(): ?string
    {
        return $this->approvedAt;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function markRead(bool $isRead = true): self
    {
        $this->isRead = $isRead;
        return $this;
    }

    public function isArchived(): bool
    {
        return $this->isArchived;
    }

    public function markArchived(bool $isArchived = true): self
    {
        $this->isArchived = $isArchived;
        return $this;
    }

    /**
     * @param array<int|string, mixed> $entry
 */public static function fromArray(array $entry): self
    {
        $comment = new self(
            (string) ($entry['articleSlug'] ?? $entry['articleId'] ?? ''),
            (string) ($entry['author'] ?? 'Anonymous'),
            (string) ($entry['content'] ?? '')
        );

        $reflection = new \ReflectionClass($comment);
        foreach (['id', 'email', 'status', 'createdAt', 'approvedAt', 'isRead', 'isArchived'] as $property) {
            if (!array_key_exists($property, $entry)) {
                continue;
            }

            $prop = $reflection->getProperty($property);
            $prop->setValue($comment, $entry[$property]);
        }

        return $comment;
    }

    /**
     * {@inheritDoc}
 * @return array<int|string, mixed>
 */public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'articleSlug' => $this->articleSlug,
            'author' => $this->author,
            'email' => $this->email,
            'content' => $this->content,
            'status' => $this->status,
            'createdAt' => $this->createdAt,
            'approvedAt' => $this->approvedAt,
            'isRead' => $this->isRead,
            'isArchived' => $this->isArchived,
        ];
    }
}
