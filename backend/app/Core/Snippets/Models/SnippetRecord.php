<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Snippets\Models;

/**
 * Flat-file registry entry for a reusable content snippet (It.81f).
 */
final class SnippetRecord
{
    public function __construct(
        public readonly string $name,
        public readonly string $title,
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
            (string) ($data['title'] ?? $name),
            (bool) ($data['enabled'] ?? true),
            (int) ($data['version'] ?? 1),
            (string) ($data['updatedAt'] ?? ''),
        );
    }

    /**
     * @return array{name: string, title: string, enabled: bool, version: int, updatedAt: string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'title' => $this->title,
            'enabled' => $this->enabled,
            'version' => $this->version,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
