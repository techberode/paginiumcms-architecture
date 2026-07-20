<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Editor\Models;

/**
 * Toolbar / schema capabilities for an editor profile (Iteration 54).
 *
 * @phpstan-type CapabilityKey string
 */
final class EditorCapabilities
{
    /** @var list<string> */
    public const ALL = [
        'bold',
        'italic',
        'underline',
        'strike',
        'heading',
        'bulletList',
        'orderedList',
        'blockquote',
        'code',
        'codeBlock',
        'link',
        'image',
        'table',
        'horizontalRule',
        'color',
    ];

    /**
     * @param list<string> $enabled
     */
    public function __construct(
        public readonly array $enabled
    ) {
    }

    public function allows(string $capability): bool
    {
        return in_array($capability, $this->enabled, true);
    }

    /**
     * @return array{enabled: list<string>}
     */
    public function toArray(): array
    {
        return ['enabled' => $this->enabled];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $enabled = $data['enabled'] ?? [];
        if (!is_array($enabled)) {
            $enabled = [];
        }

        $filtered = [];
        foreach ($enabled as $item) {
            if (!is_string($item)) {
                continue;
            }
            if (in_array($item, self::ALL, true)) {
                $filtered[] = $item;
            }
        }

        return new self(array_values(array_unique($filtered)));
    }
}
