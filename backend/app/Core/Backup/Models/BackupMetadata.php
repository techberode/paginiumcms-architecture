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
    private array $includes;
    private string $version;
    private string $status;

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

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize($size): self
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

    public function getIncludes(): array
    {
        return $this->includes;
    }

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
        ];
    }
}
