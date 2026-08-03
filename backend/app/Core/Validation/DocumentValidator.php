<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Validation;

/**
 * Validates admin-written JSON documents against registered schemas (Iteration 68).
 *
 * Maps failures to the stable ValidationException contract used by the API layer.
 */
final class DocumentValidator
{
    public function __construct(
        private DocumentSchemaRegistry $registry,
    ) {
    }

    /**
     * @param array<string, mixed> $document
     */
    public function validate(string $documentType, int $version, array $document): void
    {
        $schema = $this->registry->get($documentType, $version);
        if ($schema === null) {
            throw new ValidationException([
                'schema' => [sprintf('Unknown document schema: %s@%d', $documentType, $version)],
            ], 'Unknown document schema');
        }

        $errors = $this->validateAgainstSchema($document, $schema, '');
        if ($errors !== []) {
            throw new ValidationException($errors, 'Document validation failed');
        }
    }

    /**
     * @param array<string, mixed> $schema
     * @return array<string, list<string>>
     */
    private function validateAgainstSchema(mixed $value, array $schema, string $path): array
    {
        $errors = [];

        if (isset($schema['type'])) {
            $typeErrors = $this->validateType($value, (string) $schema['type'], $path);
            if ($typeErrors !== []) {
                return $typeErrors;
            }
        }

        if (($schema['type'] ?? null) === 'object' && is_array($value)) {
            if (isset($schema['required']) && is_array($schema['required'])) {
                foreach ($schema['required'] as $requiredKey) {
                    if (!is_string($requiredKey)) {
                        continue;
                    }
                    if (!array_key_exists($requiredKey, $value)) {
                        $fieldPath = $path === '' ? $requiredKey : $path . '.' . $requiredKey;
                        $errors[$fieldPath][] = 'Field is required.';
                    }
                }
            }

            $additionalProperties = $schema['additionalProperties'] ?? true;
            if (is_array($additionalProperties)) {
                foreach ($value as $key => $child) {
                    if (!is_string($key)) {
                        continue;
                    }
                    $childPath = $path === '' ? $key : $path . '.' . $key;
                    $childErrors = $this->validateAgainstSchema($child, $additionalProperties, $childPath);
                    $errors = array_merge($errors, $childErrors);
                }
            } elseif ($additionalProperties === false) {
                foreach (array_keys($value) as $key) {
                    $fieldPath = $path === '' ? (string) $key : $path . '.' . $key;
                    $errors[$fieldPath][] = 'Additional property is not allowed.';
                }
            }
        }

        if (isset($schema['enum']) && is_array($schema['enum'])) {
            if (!in_array($value, $schema['enum'], true)) {
                $errors[$path !== '' ? $path : 'value'][] = 'Value is not in the allowed set.';
            }
        }

        return $errors;
    }

    /**
     * @return array<string, list<string>>
     */
    private function validateType(mixed $value, string $type, string $path): array
    {
        $field = $path !== '' ? $path : 'value';
        $ok = match ($type) {
            'object' => is_array($value),
            'array' => is_array($value),
            'string' => is_string($value),
            'integer' => is_int($value),
            'number' => is_int($value) || is_float($value),
            'boolean' => is_bool($value),
            'null' => $value === null,
            default => true,
        };

        if (!$ok) {
            return [$field => [sprintf('Expected type %s.', $type)]];
        }

        return [];
    }
}
