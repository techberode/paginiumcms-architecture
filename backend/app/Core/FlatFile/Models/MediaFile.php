<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Models;

use DateTimeImmutable;
use JsonSerializable;

/**
 * Model pre mediálny súbor.
 */
class MediaFile implements JsonSerializable
{
    private string $id;
    private string $path;
    private string $fileName;
    private string $url;
    private int $sizeBytes;
    private string $mimeType;
    private int $uploadedAt;
    private string $altText = '';
    private string $folder = '';
    private string $title = '';

    public function __construct()
    {
        $this->id = uniqid('media_', true);
        $this->uploadedAt = time();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function getSizeBytes(): int
    {
        return $this->sizeBytes;
    }

    public function setSizeBytes(int $sizeBytes): self
    {
        $this->sizeBytes = $sizeBytes;
        return $this;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getUploadedAt(): int
    {
        return $this->uploadedAt;
    }

    public function setUploadedAt(int $uploadedAt): self
    {
        $this->uploadedAt = $uploadedAt;
        return $this;
    }

    public function getAltText(): string
    {
        return $this->altText;
    }

    public function setAltText(string $altText): self
    {
        $this->altText = $altText;
        return $this;
    }

    public function getFolder(): string
    {
        return $this->folder;
    }

    public function setFolder(string $folder): self
    {
        $this->folder = $folder;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getUploadedAtDateTime(): DateTimeImmutable
    {
        return new DateTimeImmutable('@' . $this->uploadedAt);
    }

    public function isImage(): bool
    {
        return strpos($this->mimeType, 'image/') === 0;
    }

    public function isSvg(): bool
    {
        return $this->mimeType === 'image/svg+xml';
    }

    public function getExtension(): string
    {
        return pathinfo($this->fileName, PATHINFO_EXTENSION);
    }

    /**
     * {@inheritDoc}
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'path' => $this->path,
            'fileName' => $this->fileName,
            'url' => $this->url,
            'sizeBytes' => $this->sizeBytes,
            'mimeType' => $this->mimeType,
            'uploadedAt' => $this->uploadedAt,
            'altText' => $this->altText,
            'folder' => $this->folder,
            'title' => $this->title,
        ];
    }
}
