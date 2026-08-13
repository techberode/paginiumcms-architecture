<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security\Services;

use DOMDocument;
use DOMElement;

/**
 * Strips dangerous HTML attributes and URI schemes (XSS hardening beyond strip_tags).
 */
final class HtmlDomSanitizer
{
    /** @var list<string> */
    private const GLOBAL_ATTRS = ['class', 'title', 'lang', 'dir', 'role'];

    /** @var array<string, list<string>> */
    private const TAG_ATTRS = [
        'a' => ['href', 'target', 'rel', 'hreflang'],
        'img' => ['src', 'alt', 'width', 'height', 'loading', 'decoding'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan', 'scope'],
        'ol' => ['start', 'type', 'reversed'],
        'ul' => ['type'],
        'li' => ['value'],
        'blockquote' => ['cite'],
        'code' => ['class'],
        'pre' => ['class'],
    ];

    /**
     * @param list<string> $allowedTags Lowercase tag names without brackets.
     */
    public function sanitize(string $html, array $allowedTags): string
    {
        if (trim($html) === '') {
            return '';
        }

        $allowed = array_fill_keys($allowedTags, true);
        if ($allowed === []) {
            return '';
        }

        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument('1.0', 'UTF-8');
        $wrapped = '<?xml encoding="UTF-8"><div id="paginium-root">' . $html . '</div>';
        $document->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('paginium-root');
        if (!$root instanceof DOMElement) {
            return '';
        }

        $this->sanitizeChildren($root, $allowed);

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return $output;
    }

    /**
     * @param array<string, true> $allowedTags
     */
    private function sanitizeChildren(DOMElement $parent, array $allowedTags): void
    {
        $toRemove = [];
        foreach ($parent->childNodes as $child) {
            if (!$child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);
            if (!isset($allowedTags[$tag])) {
                $toRemove[] = $child;
                continue;
            }

            $this->sanitizeAttributes($child);
            $this->sanitizeChildren($child, $allowedTags);
        }

        foreach ($toRemove as $node) {
            $parent->removeChild($node);
        }
    }

    private function sanitizeAttributes(DOMElement $element): void
    {
        $tag = strtolower($element->tagName);
        $allowed = array_merge(self::GLOBAL_ATTRS, self::TAG_ATTRS[$tag] ?? []);

        $remove = [];
        foreach ($element->attributes ?? [] as $attribute) {
            $name = strtolower($attribute->name);
            if (str_starts_with($name, 'on') || $name === 'style' || $name === 'xmlns' || $name === 'formaction') {
                $remove[] = $attribute->name;
                continue;
            }

            if (!in_array($name, $allowed, true)) {
                $remove[] = $attribute->name;
                continue;
            }

            if (in_array($name, ['href', 'src', 'cite', 'poster', 'srcset'], true)
                && !$this->isSafeUri($attribute->value)) {
                $remove[] = $attribute->name;
            }
        }

        foreach ($remove as $name) {
            $element->removeAttribute($name);
        }

        if ($tag === 'a' && strtolower($element->getAttribute('target')) === '_blank') {
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private function isSafeUri(string $uri): bool
    {
        $value = trim(html_entity_decode($uri, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($value === '' || $value === '#') {
            return true;
        }

        if (preg_match('/^\s*(javascript|vbscript|data)\s*:/i', $value) === 1) {
            return false;
        }

        if (preg_match('~^(https?://|mailto:|tel:|/|\./|\.\./|#)~i', $value) === 1) {
            return true;
        }

        // Relative paths without scheme (e.g. blog/post)
        return preg_match('~^[a-z][a-z0-9+\-.]*:~i', $value) !== 1;
    }
}
