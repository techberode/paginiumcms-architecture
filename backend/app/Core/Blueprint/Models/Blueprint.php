<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Blueprint\Models;

/**
 * Flat-file content type blueprint (Iteration 12).
 */
final class Blueprint
{
    /**
     * @param list<FieldDefinition> $fields
     */
    public function __construct(
        public readonly string $type,
        public readonly string $label,
        public readonly string $description,
        public readonly array $fields,
        public readonly bool $system = false,
        public readonly ?string $updatedAt = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $fields = [];
        foreach ($data['fields'] ?? [] as $field) {
            if (!is_array($field)) {
                continue;
            }

            $fields[] = FieldDefinition::fromArray($field);
        }

        return new self(
            type: (string) ($data['type'] ?? ''),
            label: (string) ($data['label'] ?? ''),
            description: (string) ($data['description'] ?? ''),
            fields: $fields,
            system: ($data['system'] ?? false) === true,
            updatedAt: isset($data['updated_at']) ? (string) $data['updated_at'] : null
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $fields = [];
        foreach ($this->fields as $field) {
            $fields[] = $field->toArray();
        }

        return [
            'type' => $this->type,
            'label' => $this->label,
            'description' => $this->description,
            'system' => $this->system,
            'fields' => $fields,
            'updated_at' => $this->updatedAt,
        ];
    }
}
