<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Blueprint\Services;

use PaginiumCMS\Core\Blueprint\Models\Blueprint;
use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Core\Validation\Validator;

/**
 * Validates content payloads against blueprint field rules (Iteration 12).
 */
final class DynamicValidator
{
    public function __construct(
        private BlueprintRepository $blueprints,
        private Validator $validator
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws ValidationException
     */
    public function validate(string $type, array $data): array
    {
        if (!$this->blueprints->exists($type)) {
            return $data;
        }

        $blueprint = $this->blueprints->get($type);

        $validated = $this->validator->validate($data, $this->rulesFromBlueprint($blueprint));

        /** @var array<string, mixed> $normalized */
        $normalized = [];
        foreach ($validated as $key => $value) {
            $normalized[(string) $key] = $value;
        }

        return $normalized;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rulesFromBlueprint(Blueprint $blueprint): array
    {
        $rules = [];
        foreach ($blueprint->fields as $field) {
            if ($field->key === '') {
                continue;
            }

            $fieldRules = $field->rules;
            if ($fieldRules === []) {
                $fieldRules = $this->defaultRulesForType($field->type);
            }

            if ($field->type === 'select' && $field->options !== []) {
                $fieldRules[] = 'in:' . implode(',', $field->options);
            }

            $rules[$field->key] = array_values(array_unique($fieldRules));
        }

        return $rules;
    }

    /**
     * @return list<string>
     */
    private function defaultRulesForType(string $type): array
    {
        return match ($type) {
            'slug' => ['string', 'slug'],
            'markdown', 'textarea', 'text' => ['string'],
            'number' => ['number'],
            'bool' => ['bool'],
            'email' => ['email'],
            'url', 'media' => ['url'],
            'datetime' => ['string'],
            'select' => ['string'],
            default => ['string'],
        };
    }
}
