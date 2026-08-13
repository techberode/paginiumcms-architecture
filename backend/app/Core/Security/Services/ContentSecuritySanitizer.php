<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security\Services;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * HTML/XML output hardening driven by settings.contentSecurity (It.19b).
 */
final class ContentSecuritySanitizer
{
    public function __construct(
        private SettingsRepositoryInterface $settings
    ) {
    }

    public function sanitizeHtml(string $html): string
    {
        $cfg = $this->settings->group('contentSecurity');
        if (!$this->isTruthy($cfg['sanitizeHtmlOnSave'] ?? true)) {
            return $html;
        }

        if ($this->isTruthy($cfg['stripExternalEntities'] ?? true)) {
            $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html) ?? $html;
            $html = preg_replace('/<!ENTITY[^>]*>/i', '', $html) ?? $html;
        }

        if (!$this->isTruthy($cfg['allowSvgInline'] ?? false)) {
            $html = preg_replace('/<svg\b[^>]*>.*?<\/svg>/is', '', $html) ?? $html;
        }

        $allowedTagNames = $this->buildAllowedTagNames($cfg);
        $sanitized = (new HtmlDomSanitizer())->sanitize($html, $allowedTagNames);

        if (!$this->isTruthy($cfg['allowScriptTags'] ?? false)) {
            $sanitized = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $sanitized) ?? $sanitized;
        }

        return $sanitized;
    }

    /**
     * @param array<string, mixed> $cfg
     * @return list<string>
     */
    private function buildAllowedTagNames(array $cfg): array
    {
        $raw = (string) ($cfg['allowedHtmlTags'] ?? '');
        $tags = array_values(array_filter(
            array_map(static fn (string $tag): string => strtolower(trim($tag)), explode(',', $raw)),
            static fn (string $tag): bool => $tag !== '' && preg_match('/^[a-z0-9-]+$/', $tag) === 1
        ));

        if ($this->isTruthy($cfg['allowScriptTags'] ?? false) && !in_array('script', $tags, true)) {
            $tags[] = 'script';
        }

        if ($this->isTruthy($cfg['allowSvgInline'] ?? false)) {
            foreach (['svg', 'path', 'g', 'circle', 'rect', 'line', 'polyline', 'polygon'] as $svgTag) {
                if (!in_array($svgTag, $tags, true)) {
                    $tags[] = $svgTag;
                }
            }
        }

        if ($tags === []) {
            return [
                'p', 'br', 'strong', 'em', 'ul', 'ol', 'li', 'a', 'img', 'blockquote',
                'code', 'pre', 'h1', 'h2', 'h3', 'h4', 'table', 'thead', 'tbody', 'tr', 'th', 'td',
                'div', 'article', 'section', 'aside', 'span',
            ];
        }

        return $tags;
    }

    private function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value !== 0;
        }

        $normalized = strtolower(trim((string) $value));

        return !in_array($normalized, ['', '0', 'false', 'off', 'no'], true);
    }
}
