<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Extensions\Models;

/**
 * Flat-file registry entry for an installed extension (runtime state only).
 * Manifest fields (name, version, hooks) live in Http/Extensions/{id}/plugin.json.
 */
final class PluginRecord
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
