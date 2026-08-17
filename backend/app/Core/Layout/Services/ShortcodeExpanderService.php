<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Layout\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\Security\Services\ContentSecuritySanitizer;
use PaginiumCMS\Core\Snippets\Services\SnippetRepository;
use PaginiumCMS\Support\JsonHelper;

/**
 * Expands registered shortcodes in markdown/HTML bodies (It.58d).
 *
 * Source documents keep shortcode tags; expansion runs at presentation time only.
 */
final class ShortcodeExpanderService
{
    private const MAX_PASSES = 8;

    public function __construct(
        private ShortcodeRegistry $registry,
        private FileReaderInterface $reader,
        private ContentSecuritySanitizer $sanitizer,
        private ?SnippetRepository $snippets = null,
        private string $definitionsRelativeDir = 'data/shortcodes/definitions',
    ) {
    }

    public function expand(string $body): string
    {
        if ($body === '' || !str_contains($body, '[')) {
            return $body;
        }

        $expanded = $body;
        for ($pass = 0; $pass < self::MAX_PASSES; $pass++) {
            $next = $this->expandPass($expanded);
            if ($next === $expanded) {
                break;
            }
            $expanded = $next;
        }

        return $expanded;
    }

    private function expandPass(string $body): string
    {
        $body = preg_replace_callback(
            '/\[([a-z][a-z0-9_-]*)\b([^\]]*)\/\]/s',
            fn (array $matches): string => $this->renderShortcode($matches[1], $matches[2], ''),
            $body
        ) ?? $body;

        return preg_replace_callback(
            '/\[([a-z][a-z0-9_-]*)\b([^\]]*)\]((?:[^\[]|\[(?!\/\1\b))*?)\[\/\1\]/s',
            fn (array $matches): string => $this->renderShortcode($matches[1], $matches[2], $matches[3]),
            $body
        ) ?? $body;
    }

    private function renderShortcode(string $rawName, string $rawAttrs, string $inner): string
    {
        $name = trim($rawName);
        if ($name === 'snippet') {
            return $this->renderSnippetReference($rawAttrs);
        }

        $definition = $this->loadDefinition($name);
        if ($definition === null) {
            return '[' . $name . $rawAttrs . ']' . $inner . ($inner === '' ? '' : '[/' . $name . ']');
        }

        $attrs = $this->parseAttributes($rawAttrs, $definition);
        $template = (string) ($definition['expand'] ?? '');
        if ($template === '') {
            return $inner;
        }

        $rendered = str_replace('{{content}}', $inner, $template);
        foreach ($attrs as $key => $value) {
            $rendered = str_replace('{{' . $key . '}}', $value, $rendered);
        }

        return $this->sanitizer->sanitizeHtml($rendered);
    }

    private function renderSnippetReference(string $rawAttrs): string
    {
        if ($this->snippets === null) {
            return '[snippet' . $rawAttrs . '/]';
        }

        if (!preg_match('/\bname\s*=\s*"([^"]+)"/', $rawAttrs, $matches)
            && !preg_match("/\bname\s*=\s*'([^']+)'/", $rawAttrs, $matches)) {
            return '[snippet' . $rawAttrs . '/]';
        }

        $snippetName = trim($matches[1]);
        if ($snippetName === '' || !preg_match('/^[a-z][a-z0-9_-]{0,39}$/', $snippetName)) {
            return '[snippet' . $rawAttrs . '/]';
        }

        $body = $this->snippets->resolveBody($snippetName);
        if ($body === '') {
            return '[snippet' . $rawAttrs . '/]';
        }

        return $this->expand($body);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadDefinition(string $name): ?array
    {
        if (!preg_match('/^[a-z][a-z0-9_-]{0,39}$/', $name)) {
            return null;
        }

        $record = $this->registry->get($name);
        if ($record === null || !$record->enabled) {
            return null;
        }

        $relativePath = $this->definitionsRelativeDir . '/' . $name . '.json';
        if (!$this->reader->exists($relativePath)) {
            return null;
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = JsonHelper::decode($this->reader->read($relativePath));
        } catch (\Throwable) {
            return null;
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $definition
     * @return array<string, string>
     */
    private function parseAttributes(string $rawAttrs, array $definition): array
    {
        $parsed = [];
        if (preg_match_all('/([a-z][a-z0-9_-]*)\s*=\s*"([^"]*)"/', $rawAttrs, $matches, PREG_SET_ORDER) > 0) {
            foreach ($matches as $match) {
                $parsed[$match[1]] = $match[2];
            }
        }

        /** @var array<string, mixed> $schema */
        $schema = is_array($definition['attrs'] ?? null) ? $definition['attrs'] : [];
        $validated = [];
        foreach ($schema as $key => $rules) {
            $value = $parsed[$key] ?? $this->defaultAttrValue(is_array($rules) ? $rules : []);
            $validated[$key] = $this->coerceAttrValue($value, is_array($rules) ? $rules : []);
        }

        return $validated;
    }

    /**
     * @param array<string, mixed> $rules
     */
    private function defaultAttrValue(array $rules): string
    {
        $type = (string) ($rules['type'] ?? 'string');
        if ($type === 'enum') {
            $options = $rules['options'] ?? [];
            if (is_array($options) && isset($options[0]) && is_string($options[0])) {
                return $options[0];
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed> $rules
     */
    private function coerceAttrValue(string $value, array $rules): string
    {
        $type = (string) ($rules['type'] ?? 'string');
        if ($type === 'enum') {
            $options = $rules['options'] ?? [];
            if (is_array($options) && in_array($value, $options, true)) {
                return $value;
            }

            return $this->defaultAttrValue($rules);
        }

        if ($type === 'bool') {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
        }

        if ($type === 'int') {
            return (string) (int) $value;
        }

        return $value;
    }
}
