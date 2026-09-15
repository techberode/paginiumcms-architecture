<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Layout\Services;

use PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException;

/**
 * Validates operator-authored widget expand templates (It.93t-e). Same kitchen rules as shortcodes.
 */
final class WidgetDefinitionPolicy
{
    private const MAX_ID_LEN = 40;
    private const MAX_EXPAND_LEN = 20000;
    private const MAX_FIELDS = 12;
    private const KINDS = ['string', 'tone', 'percent', 'href'];

    /** @var list<string> */
    private const FORBIDDEN_EXPAND_PATTERNS = [
        '/<\s*script\b/i',
        '/javascript\s*:/i',
        '/\bon[a-z]+\s*=/i',
        '/<\s*iframe\b/i',
        '/<\s*object\b/i',
        '/<\s*embed\b/i',
        '/\{\s*\$/',
        '/<\?php/i',
        '/<\?=/',
    ];

    /**
     * @param array<string, mixed> $definition
     *
     * @throws CodePolicyViolationException
     */
    public function validate(array $definition): void
    {
        $errors = [];

        $id = trim((string) ($definition['id'] ?? $definition['name'] ?? ''));
        if ($id === '' || !preg_match('/^[a-z][a-z0-9_-]{0,' . (self::MAX_ID_LEN - 1) . '}$/', $id)) {
            $errors['schema'][] = 'Widget id must match [a-z][a-z0-9_-]{0,39}';
        }

        $label = trim((string) ($definition['label'] ?? ''));
        if (strlen($label) > 80) {
            $errors['schema'][] = 'Widget label is too long';
        }

        $fields = $definition['fields'] ?? [];
        if (!is_array($fields)) {
            $errors['schema'][] = 'Widget fields must be a list';
        } elseif (count($fields) > self::MAX_FIELDS) {
            $errors['schema'][] = 'Too many widget fields';
        } else {
            foreach ($fields as $field) {
                if (!is_array($field)) {
                    $errors['schema'][] = 'Each field must be an object';
                    continue;
                }
                $key = (string) ($field['key'] ?? '');
                $kind = (string) ($field['kind'] ?? '');
                if ($key === '' || !preg_match('/^[a-z][a-z0-9_-]*$/', $key) || $key === 'type') {
                    $errors['schema'][] = 'Invalid field key: ' . $key;
                }
                if (!in_array($kind, self::KINDS, true)) {
                    $errors['schema'][] = 'Field kind must be string|tone|percent|href: ' . $key;
                }
            }
        }

        $expand = (string) ($definition['expand'] ?? '');
        if ($expand === '') {
            $errors['schema'][] = 'Widget expand template is required';
        } elseif (strlen($expand) > self::MAX_EXPAND_LEN) {
            $errors['size'][] = 'Expand template exceeds maximum length';
        } else {
            foreach (self::FORBIDDEN_EXPAND_PATTERNS as $pattern) {
                if (preg_match($pattern, $expand) === 1) {
                    $errors['security'][] = 'Expand template contains a forbidden pattern';
                    break;
                }
            }
            if (preg_match_all('/\bclass\s*=\s*["\']([^"\']+)["\']/', $expand, $matches) > 0) {
                foreach ($matches[1] as $classList) {
                    foreach (preg_split('/\s+/', trim($classList)) ?: [] as $class) {
                        if ($class === '' || str_contains($class, '{{')) {
                            continue;
                        }
                        if (!preg_match('/^pg-[a-z0-9:_-]+$/', $class)
                            && !preg_match('/^(paginium-public-|prose)/', $class)
                        ) {
                            $errors['security'][] = 'Expand template uses non-allow-listed CSS class: ' . $class;
                            break 2;
                        }
                    }
                }
            }
        }

        if ($errors !== []) {
            throw new CodePolicyViolationException($errors);
        }
    }
}
