<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\CodePolicy\Services;

use PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException;

/**
 * Validates Monaco / flat-file shortcode definitions before they enter the registry (It.58d).
 *
 * Definitions are data + expand templates — never executable PHP. Expand bodies are scanned
 * for forbidden patterns so broken or hostile markup cannot be activated.
 */
final class ShortcodeDefinitionPolicy
{
    private const MAX_NAME_LEN = 40;
    private const MAX_EXPAND_LEN = 20000;
    private const MAX_ATTR_KEYS = 20;

    /** @var list<string> */
    private const FORBIDDEN_EXPAND_PATTERNS = [
        '/<\s*script\b/i',
        '/javascript\s*:/i',
        '/\bon[a-z]+\s*=/i',
        '/<\s*iframe\b/i',
        '/<\s*object\b/i',
        '/<\s*embed\b/i',
        '/\{\s*\$/', // block PHP-ish interpolations
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

        $name = trim((string) ($definition['name'] ?? ''));
        if ($name === '' || !preg_match('/^[a-z][a-z0-9_-]{0,' . (self::MAX_NAME_LEN - 1) . '}$/', $name)) {
            $errors['schema'][] = 'Shortcode name must match [a-z][a-z0-9_-]{0,39}';
        }

        $version = $definition['version'] ?? 1;
        if (!is_int($version) && !(is_string($version) && ctype_digit($version))) {
            $errors['schema'][] = 'Shortcode version must be an integer';
        }

        $attrs = $definition['attrs'] ?? [];
        if (!is_array($attrs)) {
            $errors['schema'][] = 'Shortcode attrs must be an object/array';
        } elseif (count($attrs) > self::MAX_ATTR_KEYS) {
            $errors['schema'][] = 'Too many shortcode attribute keys';
        } else {
            foreach ($attrs as $key => $schema) {
                if (!is_string($key) || !preg_match('/^[a-z][a-z0-9_-]*$/', $key)) {
                    $errors['schema'][] = 'Invalid attribute key: ' . (string) $key;
                    continue;
                }
                if (!is_array($schema)) {
                    $errors['schema'][] = 'Attribute schema must be an object: ' . $key;
                    continue;
                }
                $type = (string) ($schema['type'] ?? '');
                if (!in_array($type, ['string', 'enum', 'int', 'bool'], true)) {
                    $errors['schema'][] = 'Attribute type must be string|enum|int|bool: ' . $key;
                }
                if ($type === 'enum') {
                    $options = $schema['options'] ?? null;
                    if (!is_array($options) || $options === []) {
                        $errors['schema'][] = 'Enum attribute requires non-empty options: ' . $key;
                    }
                }
            }
        }

        $expand = (string) ($definition['expand'] ?? '');
        if ($expand === '') {
            $errors['schema'][] = 'Shortcode expand template is required';
        } elseif (strlen($expand) > self::MAX_EXPAND_LEN) {
            $errors['size'][] = 'Expand template exceeds maximum length';
        } else {
            foreach (self::FORBIDDEN_EXPAND_PATTERNS as $pattern) {
                if (preg_match($pattern, $expand) === 1) {
                    $errors['security'][] = 'Expand template contains a forbidden pattern';
                    break;
                }
            }
            // Only allow-listed layout utility classes if class= appears.
            if (preg_match_all('/\bclass\s*=\s*["\']([^"\']+)["\']/', $expand, $matches) > 0) {
                foreach ($matches[1] as $classList) {
                    foreach (preg_split('/\s+/', trim($classList)) ?: [] as $class) {
                        if ($class === '') {
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

    /**
     * @throws CodePolicyViolationException
     */
    public function validateJson(string $json): void
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            throw new CodePolicyViolationException([
                'syntax' => ['Shortcode definition must be valid JSON object'],
            ]);
        }

        $this->validate($decoded);
    }
}
