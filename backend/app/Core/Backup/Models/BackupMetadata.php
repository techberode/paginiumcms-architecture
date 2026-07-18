<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Backup\Models;

use JsonSerializable;

class BackupMetadata implements JsonSerializable
{
    private string $id;
    private string $name;
    private string $createdAt;
    private int $size;
    private string $filePath;
    /** @var array<int|string, mixed> */
    private array $includes;
    private string $version;
    private string $status;
    private string $sha256 = '';

    public function __construct()
    {
        $this->id = uniqid('backup_', true);
        $this->name = '';
        $this->createdAt = date('Y-m-d H:i:s');
        $this->size = 0;
        $this->filePath = '';
        $this->includes = [];
        $this->version = '2.0';
        $this->status = 'in_progress';
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int|string $size): self
    {
        $this->size = (int)$size;
        return $this;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): self
    {
        $this->filePath = $filePath;
        return $this;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getIncludes(): array
    {
        return $this->includes;
    }

    /**
     * @param array<int|string, mixed> $includes
     */
    public function setIncludes(array $includes): self
    {
        $this->includes = $includes;
        return $this;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getSha256(): string
    {
        return $this->sha256;
    }

    public function setSha256(string $sha256): self
    {
        $this->sha256 = $sha256;

        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function getSizeFormatted(): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $size = $this->size;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 2) . ' ' . $units[$i];
    }

    /**
     * @return array<int|string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'createdAt' => $this->createdAt,
            'size' => $this->size,
            'sizeFormatted' => $this->getSizeFormatted(),
            'filePath' => $this->filePath,
            'includes' => $this->includes,
            'version' => $this->version,
            'status' => $this->status,
            'sha256' => $this->sha256,
        ];
    }
}
