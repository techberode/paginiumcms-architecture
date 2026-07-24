<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Models;

use JsonSerializable;

/**
 * Model pre položku navigácie (It.56 — rich menu fields).
 */
class NavigationItem implements JsonSerializable
{
    public const ICON_TYPES = ['none', 'lucide', 'media'];
    public const THUMBNAIL_SIZES = ['sm', 'md', 'lg'];
    public const MAX_DESCRIPTION_LENGTH = 160;

    private string $id;
    private string $label;
    private string $path;
    private string $target = '_self';
    private int $order = 0;
    private ?string $parentId = null;
    private string $description = '';
    private string $iconType = 'none';
    private ?string $iconValue = null;
    private bool $previewOnHover = false;
    private float $previewScale = 1.5;
    private string $thumbnailSize = 'sm';

    public function __construct(string $label, string $path)
    {
        $this->id = uniqid('nav_', true);
        $this->label = $label;
        $this->path = $path;
    }

    /**
     * @param array<string, mixed> $entry
     */
    public static function fromPayload(array $entry, int $fallbackOrder = 0): ?self
    {
        $label = trim((string) ($entry['label'] ?? ''));
        $path = trim((string) ($entry['path'] ?? ''));
        if ($label === '' || $path === '') {
            return null;
        }

        $item = new self($label, $path);

        if (!empty($entry['id'])) {
            $item->id = (string) $entry['id'];
        }

        $item->order = (int) ($entry['order'] ?? $fallbackOrder);
        if (!empty($entry['target'])) {
            $item->setTarget((string) $entry['target']);
        }
        if (array_key_exists('parentId', $entry)) {
            $item->parentId = $entry['parentId'] !== null ? (string) $entry['parentId'] : null;
        }

        $item->description = trim((string) ($entry['description'] ?? ''));
        $item->iconType = self::normalizeIconType((string) ($entry['iconType'] ?? 'none'));
        $item->iconValue = self::nullableString($entry['iconValue'] ?? null);
        $item->previewOnHover = self::toBool($entry['previewOnHover'] ?? false);
        $item->previewScale = self::normalizePreviewScale($entry['previewScale'] ?? 1.5);
        $item->thumbnailSize = self::normalizeThumbnailSize((string) ($entry['thumbnailSize'] ?? 'sm'));

        if ($item->iconType === 'none' && array_key_exists('icon', $entry) && $entry['icon'] !== null && $entry['icon'] !== '') {
            $legacy = trim((string) $entry['icon']);
            if ($legacy !== '') {
                $item->iconType = str_starts_with($legacy, '/') || str_starts_with($legacy, 'http')
                    ? 'media'
                    : 'lucide';
                $item->iconValue = $legacy;
            }
        }

        if ($item->iconType === 'none') {
            $item->iconValue = null;
        }

        return $item;
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
        if (!in_array($target, ['_self', '_blank', '_parent', '_top'], true)) {
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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getIconType(): string
    {
        return $this->iconType;
    }

    public function getIconValue(): ?string
    {
        return $this->iconValue;
    }

    public function isPreviewOnHover(): bool
    {
        return $this->previewOnHover;
    }

    public function getPreviewScale(): float
    {
        return $this->previewScale;
    }

    public function getThumbnailSize(): string
    {
        return $this->thumbnailSize;
    }

    public function isExternalLink(): bool
    {
        return str_starts_with($this->path, 'http://') || str_starts_with($this->path, 'https://');
    }

    public function isInternalLink(): bool
    {
        return !$this->isExternalLink() && str_starts_with($this->path, '/');
    }

    /**
     * @return array<string, mixed>
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
            'description' => $this->description,
            'iconType' => $this->iconType,
            'iconValue' => $this->iconValue,
            'previewOnHover' => $this->previewOnHover,
            'previewScale' => $this->previewScale,
            'thumbnailSize' => $this->thumbnailSize,
        ];
    }

    private static function normalizeIconType(string $value): string
    {
        $normalized = strtolower(trim($value));

        return in_array($normalized, self::ICON_TYPES, true) ? $normalized : 'none';
    }

    private static function normalizeThumbnailSize(string $value): string
    {
        $normalized = strtolower(trim($value));

        return in_array($normalized, self::THUMBNAIL_SIZES, true) ? $normalized : 'sm';
    }

    private static function normalizePreviewScale(mixed $value): float
    {
        $scale = is_numeric($value) ? (float) $value : 1.5;

        return max(1.0, min(3.0, round($scale, 1)));
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private static function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value !== 0;
        }

        $normalized = strtolower(trim((string) $value));

        return !in_array($normalized, ['', '0', 'false', 'off', 'no'], true);
    }
}
