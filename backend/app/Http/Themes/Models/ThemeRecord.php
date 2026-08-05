<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Themes\Models;

/**
 * Flat-file registry entry for an installed theme package (It.67b).
 */
final class ThemeRecord
{
    public function __construct(
        public readonly string $id,
        public readonly bool $enabled,
        public readonly string $installedAt,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(string $id, array $data): self
    {
        return new self(
            $id,
            (bool) ($data['enabled'] ?? false),
            (string) ($data['installedAt'] ?? ''),
        );
    }

    /**
     * @return array{id: string, enabled: bool, installedAt: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'enabled' => $this->enabled,
            'installedAt' => $this->installedAt,
        ];
    }
}
