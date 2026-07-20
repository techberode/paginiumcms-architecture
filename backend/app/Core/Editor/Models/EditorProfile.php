<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Editor\Models;

/**
 * Named editor profile with allowed capabilities and supported modes.
 */
final class EditorProfile
{
    /**
     * @param list<string> $modes markdown|wysiwyg
     */
    public function __construct(
        public readonly string $id,
        public readonly string $label,
        public readonly string $description,
        public readonly EditorCapabilities $capabilities,
        public readonly array $modes = ['markdown', 'wysiwyg']
    ) {
    }

    /**
     * @return array{id: string, label: string, description: string, capabilities: array{enabled: list<string>}, modes: list<string>}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'description' => $this->description,
            'capabilities' => $this->capabilities->toArray(),
            'modes' => $this->modes,
        ];
    }
}
