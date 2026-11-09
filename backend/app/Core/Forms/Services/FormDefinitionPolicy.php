<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Forms\Services;

use InvalidArgumentException;

/**
 * Validates flat-file public form definitions (It.101).
 */
final class FormDefinitionPolicy
{
    /** @var list<string> */
    public const FIELD_TYPES = ['text', 'email', 'tel', 'textarea', 'checkbox', 'url', 'hidden'];

    /**
     * @param array<int|string, mixed> $payload
     * @return array<string, mixed>
     */
    public function validate(array $payload): array
    {
        $id = $this->normalizeId((string) ($payload['id'] ?? ''));
        $title = trim((string) ($payload['title'] ?? ''));
        if ($title === '' || mb_strlen($title) > 200) {
            throw new InvalidArgumentException('Form title is invalid.');
        }

        $fields = $payload['fields'] ?? null;
        if (!is_array($fields) || $fields === []) {
            throw new InvalidArgumentException('Form must declare at least one field.');
        }

        $normalizedFields = [];
        $seenKeys = [];
        foreach ($fields as $row) {
            if (!is_array($row)) {
                throw new InvalidArgumentException('Form field row is invalid.');
            }
            $field = $this->normalizeField($row);
            if (isset($seenKeys[$field['key']])) {
                throw new InvalidArgumentException('Duplicate field key.');
            }
            $seenKeys[$field['key']] = true;
            $normalizedFields[] = $field;
        }

        $routing = is_array($payload['routing'] ?? null) ? $payload['routing'] : [];
        $channel = trim((string) ($routing['channel'] ?? 'contact'));
        if ($channel === '' || strlen($channel) > 64 || !preg_match('/^[a-z][a-z0-9_-]*$/', $channel)) {
            throw new InvalidArgumentException('Routing channel is invalid.');
        }

        $subject = trim((string) ($routing['subject'] ?? ''));
        if (mb_strlen($subject) > 200) {
            throw new InvalidArgumentException('Routing subject is invalid.');
        }

        $priority = strtolower(trim((string) ($routing['priority'] ?? 'normal')));
        if (!in_array($priority, ['low', 'normal', 'high', 'urgent'], true)) {
            $priority = 'normal';
        }

        $preset = trim((string) ($payload['preset'] ?? ''));

        return [
            'schemaVersion' => 1,
            'id' => $id,
            'title' => $title,
            'description' => trim((string) ($payload['description'] ?? '')),
            'enabled' => (bool) ($payload['enabled'] ?? true),
            'preset' => $preset,
            'routing' => [
                'channel' => $channel,
                'subject' => $subject,
                'priority' => $priority,
            ],
            'fields' => $normalizedFields,
        ];
    }

    public function normalizeId(string $id): string
    {
        $id = strtolower(trim($id));
        if ($id === '' || strlen($id) > 64 || !preg_match('/^[a-z][a-z0-9_-]*$/', $id)) {
            throw new InvalidArgumentException('Form id is invalid.');
        }

        return $id;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeField(array $row): array
    {
        $key = strtolower(trim((string) ($row['key'] ?? '')));
        if ($key === '' || strlen($key) > 64 || !preg_match('/^[a-z][a-z0-9_-]*$/', $key)) {
            throw new InvalidArgumentException('Field key is invalid.');
        }

        $type = strtolower(trim((string) ($row['type'] ?? 'text')));
        if (!in_array($type, self::FIELD_TYPES, true)) {
            throw new InvalidArgumentException('Field type is invalid.');
        }

        $label = trim((string) ($row['label'] ?? ''));
        if ($label !== '' && mb_strlen($label) > 200) {
            throw new InvalidArgumentException('Field label is invalid.');
        }

        $max = (int) ($row['maxLength'] ?? 0);
        $min = (int) ($row['minLength'] ?? 0);
        if ($max < 0 || $min < 0 || ($max > 0 && $min > $max)) {
            throw new InvalidArgumentException('Field length limits are invalid.');
        }

        return [
            'key' => $key,
            'type' => $type,
            'label' => $label,
            'required' => (bool) ($row['required'] ?? false),
            'placeholder' => trim((string) ($row['placeholder'] ?? '')),
            'default' => trim((string) ($row['default'] ?? '')),
            'minLength' => $min > 0 ? $min : null,
            'maxLength' => $max > 0 ? $max : null,
        ];
    }
}
