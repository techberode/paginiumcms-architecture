<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Blueprint\Models;

/**
 * Single field in a content blueprint (Iteration 12).
 *
 * @phpstan-type FieldDefinitionArray array{
 *     key: string,
 *     type: string,
 *     label: string,
 *     rules?: list<string>,
 *     options?: list<string>,
 *     help?: string,
 *     default?: mixed
 * }
 */
final class FieldDefinition
{
    /**
     * @param list<string> $rules
     * @param list<string> $options
     */
    public function __construct(
        public readonly string $key,
        public readonly string $type,
        public readonly string $label,
        public readonly array $rules = [],
        public readonly array $options = [],
        public readonly string $help = '',
        public readonly mixed $default = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $rules = [];
        foreach ($data['rules'] ?? [] as $rule) {
            if (is_string($rule) && $rule !== '') {
                $rules[] = $rule;
            }
        }

        $options = [];
        foreach ($data['options'] ?? [] as $option) {
            if (is_string($option) && $option !== '') {
                $options[] = $option;
            }
        }

        return new self(
            key: (string) ($data['key'] ?? ''),
            type: (string) ($data['type'] ?? 'text'),
            label: (string) ($data['label'] ?? ''),
            rules: $rules,
            options: $options,
            help: (string) ($data['help'] ?? ''),
            default: $data['default'] ?? null
        );
    }

    /**
     * @return FieldDefinitionArray
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'type' => $this->type,
            'label' => $this->label,
            'rules' => $this->rules,
            'options' => $this->options,
            'help' => $this->help,
            'default' => $this->default,
        ];
    }
}
