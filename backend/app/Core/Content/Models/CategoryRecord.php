<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Content\Models;

/**
 * Registry row for content taxonomy category (It.84a).
 *
 * @phpstan-type CategoryRecordArray array{slug: string, label: string}
 */
final class CategoryRecord
{
    public function __construct(
        public readonly string $slug,
        public readonly string $label,
    ) {
    }

    /**
     * @return CategoryRecordArray
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'label' => $this->label,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(string $slug, array $data): self
    {
        $normalizedSlug = CategoryRecord::normalizeSlug($slug);
        $label = trim((string) ($data['label'] ?? $normalizedSlug));

        return new self($normalizedSlug, $label !== '' ? $label : $normalizedSlug);
    }

    public static function normalizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        if ($slug === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+){0,19}$/', $slug)) {
            return '';
        }

        return $slug;
    }
}
