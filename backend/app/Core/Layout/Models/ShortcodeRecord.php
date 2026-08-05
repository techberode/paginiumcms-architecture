<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Layout\Models;

/**
 * Flat-file registry entry for an installed shortcode definition (It.67a).
 */
final class ShortcodeRecord
{
    public function __construct(
        public readonly string $name,
        public readonly bool $enabled,
        public readonly int $version,
        public readonly string $updatedAt,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(string $name, array $data): self
    {
        return new self(
            $name,
            (bool) ($data['enabled'] ?? true),
            (int) ($data['version'] ?? 1),
            (string) ($data['updatedAt'] ?? ''),
        );
    }

    /**
     * @return array{name: string, enabled: bool, version: int, updatedAt: string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'enabled' => $this->enabled,
            'version' => $this->version,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
