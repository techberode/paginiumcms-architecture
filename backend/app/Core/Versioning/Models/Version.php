<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Versioning\Models;

use JsonSerializable;

class Version implements JsonSerializable
{
    private string $id;
    private string $contentId;
    private string $contentType;
    private int $version;
    private string $content;
    private string $frontMatter;
    private string $createdAt;
    private string $createdBy;
    private string $message;
    private ?array $diff;

public function __construct()
{
    $this->id = uniqid('ver_', true);
    $this->contentId = '';
    $this->contentType = '';
    $this->version = 1;
    $this->content = '';
    $this->frontMatter = '';
    $this->createdAt = date('Y-m-d H:i:s');
    $this->createdBy = '';
    $this->message = '';
    $this->diff = null;
}

    // Getters a setters
    public function getId(): string { return $this->id; }
    public function getContentId(): string { return $this->contentId; }
    public function setContentId(string $id): self { $this->contentId = $id; return $this; }
    public function getContentType(): string { return $this->contentType; }
    public function setContentType(string $type): self { $this->contentType = $type; return $this; }
    public function getVersion(): int { return $this->version; }
    public function setVersion(int $version): self { $this->version = $version; return $this; }
    public function getContent(): string { return $this->content; }
    public function setContent(string $content): self { $this->content = $content; return $this; }
    public function getFrontMatter(): string { return $this->frontMatter; }
    public function setFrontMatter(string $frontMatter): self { $this->frontMatter = $frontMatter; return $this; }
    public function getCreatedAt(): string { return $this->createdAt; }
    public function getCreatedBy(): string { return $this->createdBy; }
    public function setCreatedBy(string $userId): self { $this->createdBy = $userId; return $this; }
    public function getMessage(): string { return $this->message; }
    public function setMessage(string $message): self { $this->message = $message; return $this; }
    public function getDiff(): ?array { return $this->diff; }
    public function setDiff(?array $diff): self { $this->diff = $diff; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'contentId' => $this->contentId,
            'contentType' => $this->contentType,
            'version' => $this->version,
            'content' => $this->content,
            'frontMatter' => $this->frontMatter,
            'createdAt' => $this->createdAt,
            'createdBy' => $this->createdBy,
            'message' => $this->message,
            'diff' => $this->diff,
        ];
    }

    public function jsonSerialize(): array { return $this->toArray(); }
}
