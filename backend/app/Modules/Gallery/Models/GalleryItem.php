<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Gallery\Models;

use JsonSerializable;

final class GalleryItem implements JsonSerializable
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
    ];

    private string $id;
    private string $title;
    private string $description;
    private string $mediaPath;
    private ?string $featureTag;
    private ?string $linkUrl;
    private int $sortOrder;
    private string $status;
    private ?string $publishedAt;
    private string $createdAt;
    private string $updatedAt;

    public function __construct(string $title, string $mediaPath)
    {
        $this->id = uniqid('gallery_', true);
        $this->title = $title;
        $this->description = '';
        $this->mediaPath = $mediaPath;
        $this->featureTag = null;
        $this->linkUrl = null;
        $this->sortOrder = 0;
        $this->status = self::STATUS_DRAFT;
        $this->publishedAt = null;
        $now = date('c');
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getItemPath(): string
    {
        return 'data/gallery/items/' . $this->id . '.json';
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getMediaPath(): string
    {
        return $this->mediaPath;
    }

    public function getFeatureTag(): ?string
    {
        return $this->featureTag;
    }

    public function getLinkUrl(): ?string
    {
        return $this->linkUrl;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPublishedAt(): ?string
    {
        return $this->publishedAt;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function applyPayload(array $payload): void
    {
        if (array_key_exists('title', $payload)) {
            $this->title = trim((string) $payload['title']);
        }
        if (array_key_exists('description', $payload)) {
            $this->description = trim((string) $payload['description']);
        }
        if (array_key_exists('mediaPath', $payload)) {
            $this->mediaPath = trim((string) $payload['mediaPath']);
        }
        if (array_key_exists('featureTag', $payload)) {
            $tag = trim((string) $payload['featureTag']);
            $this->featureTag = $tag === '' ? null : $tag;
        }
        if (array_key_exists('linkUrl', $payload)) {
            $url = trim((string) $payload['linkUrl']);
            $this->linkUrl = $url === '' ? null : $url;
        }
        if (array_key_exists('sortOrder', $payload) && is_numeric($payload['sortOrder'])) {
            $this->sortOrder = (int) $payload['sortOrder'];
        }
        if (array_key_exists('status', $payload)) {
            $status = (string) $payload['status'];
            if (in_array($status, self::STATUSES, true)) {
                $wasPublished = $this->isPublished();
                $this->status = $status;
                if ($status === self::STATUS_PUBLISHED && !$wasPublished) {
                    $this->publishedAt = date('c');
                }
                if ($status === self::STATUS_DRAFT) {
                    $this->publishedAt = null;
                }
            }
        }
        $this->updatedAt = date('c');
    }

    /**
     * @param array<string, mixed> $entry
     */
    public static function fromArray(array $entry, string $id): self
    {
        $item = new self(
            (string) ($entry['title'] ?? ''),
            (string) ($entry['mediaPath'] ?? '')
        );

        $reflection = new \ReflectionClass($item);
        $idProp = $reflection->getProperty('id');
        $idProp->setValue($item, $id);

        $item->description = trim((string) ($entry['description'] ?? ''));
        $tag = trim((string) ($entry['featureTag'] ?? ''));
        $item->featureTag = $tag === '' ? null : $tag;
        $url = trim((string) ($entry['linkUrl'] ?? ''));
        $item->linkUrl = $url === '' ? null : $url;
        $item->sortOrder = (int) ($entry['sortOrder'] ?? 0);

        $status = (string) ($entry['status'] ?? self::STATUS_DRAFT);
        $item->status = in_array($status, self::STATUSES, true) ? $status : self::STATUS_DRAFT;

        $publishedAt = trim((string) ($entry['publishedAt'] ?? ''));
        $item->publishedAt = $publishedAt === '' ? null : $publishedAt;

        if (!empty($entry['createdAt'])) {
            $createdProp = $reflection->getProperty('createdAt');
            $createdProp->setValue($item, (string) $entry['createdAt']);
        }
        if (!empty($entry['updatedAt'])) {
            $updatedProp = $reflection->getProperty('updatedAt');
            $updatedProp->setValue($item, (string) $entry['updatedAt']);
        }

        return $item;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'mediaPath' => $this->mediaPath,
            'featureTag' => $this->featureTag,
            'linkUrl' => $this->linkUrl,
            'sortOrder' => $this->sortOrder,
            'status' => $this->status,
            'publishedAt' => $this->publishedAt,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
