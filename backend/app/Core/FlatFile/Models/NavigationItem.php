<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Models;

use JsonSerializable;

/**
 * Model pre položku navigácie.
 */
class NavigationItem implements JsonSerializable
{
    private string $id;
    private string $label;
    private string $path;
    private string $target = '_self';
    private int $order = 0;
    private ?string $parentId = null;
    private ?string $icon = null;

    public function __construct(string $label, string $path)
    {
        $this->id = uniqid('nav_', true);
        $this->label = $label;
        $this->path = $path;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
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

    public function getTarget(): string
    {
        return $this->target;
    }

    public function setTarget(string $target): self
    {
        if (!in_array($target, ['_self', '_blank', '_parent', '_top'])) {
            throw new InvalidArgumentException('Neplatná hodnota pre target');
        }

        $this->target = $target;
        return $this;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): self
    {
        $this->order = $order;
        return $this;
    }

    public function getParentId(): ?string
    {
        return $this->parentId;
    }

    public function setParentId(?string $parentId): self
    {
        $this->parentId = $parentId;
        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function isExternalLink(): bool
    {
        return strpos($this->path, 'http://') === 0 || strpos($this->path, 'https://') === 0;
    }

    public function isInternalLink(): bool
    {
        return !$this->isExternalLink() && strpos($this->path, '/') === 0;
    }

    public function hasChildren(): bool
    {
        return $this->parentId !== null;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'path' => $this->path,
            'target' => $this->target,
            'order' => $this->order,
            'parentId' => $this->parentId,
            'icon' => $this->icon,
        ];
    }
}
