<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Mail\Services;

use DOMDocument;
use DOMElement;

/**
 * Mail HTML for a sandboxed iframe (Roundcube-style). Keeps layout CSS, drops active content.
 */
final class MailHtmlSanitizer
{
    /** @var list<string> */
    private const DROP_TAGS = [
        'script', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select',
        'link', 'base', 'frame', 'frameset', 'applet', 'svg', 'math', 'video', 'audio', 'source',
        'track', 'template', 'noscript',
    ];

    public static function document(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }
        if (function_exists('mb_strlen') && mb_strlen($html) > 250000) {
            $html = mb_substr($html, 0, 250000);
        } elseif (strlen($html) > 250000) {
            $html = substr($html, 0, 250000);
        }

        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument('1.0', 'UTF-8');
        $wrapped = '<?xml encoding="UTF-8">' . $html;
        $document->loadHTML($wrapped, LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        self::stripNodes($document);
        $root = $document->documentElement;
        if ($root instanceof DOMElement) {
            self::sanitizeTree($root);
        }

        $out = $document->saveHTML();
        if (!is_string($out) || trim($out) === '') {
            return '';
        }

        if (!str_contains(strtolower($out), '<meta charset')) {
            $out = preg_replace('/<head(\s[^>]*)?>/i', '<head$1><meta charset="UTF-8">', $out, 1) ?? $out;
        }

        return self::decodeUtf8Entities($out);
    }

    /**
     * libxml saveHTML emits numeric/named entities for non-ASCII. Keep UTF-8 in the iframe
     * without turning &lt; / &amp; back into markup.
     */
    private static function decodeUtf8Entities(string $html): string
    {
        $decoded = preg_replace_callback(
            '/&(#(?:x[0-9A-Fa-f]+|[0-9]+)|[A-Za-z][A-Za-z0-9]+);/',
            static function (array $match): string {
                $name = $match[1];
                $key = strtolower($name);
                if (in_array($key, ['lt', 'gt', 'amp', 'quot', 'apos'], true)) {
                    return $match[0];
                }
                if ($name[0] === '#') {
                    $code = strtolower(substr($name, 1, 1)) === 'x'
                        ? hexdec(substr($name, 2))
                        : (int) substr($name, 1);
                    if (in_array($code, [34, 38, 39, 60, 62], true)) {
                        return $match[0];
                    }
                }
                $out = html_entity_decode($match[0], ENT_QUOTES | ENT_HTML5, 'UTF-8');

                return $out !== '' ? $out : $match[0];
            },
            $html
        );

        return is_string($decoded) ? $decoded : $html;
    }

    private static function stripNodes(DOMDocument $document): void
    {
        foreach (self::DROP_TAGS as $tag) {
            $nodes = $document->getElementsByTagName($tag);
            $remove = [];
            foreach ($nodes as $node) {
                $remove[] = $node;
            }
            foreach ($remove as $node) {
                $node->parentNode?->removeChild($node);
            }
        }
    }

    private static function sanitizeTree(DOMElement $element): void
    {
        if (strtolower($element->tagName) === 'meta') {
            $http = strtolower($element->getAttribute('http-equiv'));
            if ($http === 'refresh') {
                $element->parentNode?->removeChild($element);

                return;
            }
        }

        if (strtolower($element->tagName) === 'style') {
            $element->textContent = self::sanitizeCss($element->textContent);
        }

        $remove = [];
        foreach ($element->attributes ?? [] as $attribute) {
            $name = strtolower($attribute->name);
            $value = $attribute->value;
            if (str_starts_with($name, 'on') || $name === 'formaction' || $name === 'xmlns') {
                $remove[] = $attribute->name;
                continue;
            }
            if ($name === 'style') {
                $clean = self::sanitizeCss($value);
                if ($clean === '') {
                    $remove[] = $attribute->name;
                } else {
                    $element->setAttribute('style', $clean);
                }
                continue;
            }
            if (in_array($name, ['href', 'src', 'background', 'cite', 'poster', 'action'], true)
                && !self::isSafeUri($value)) {
                $remove[] = $attribute->name;
            }
        }
        foreach ($remove as $name) {
            $element->removeAttribute($name);
        }

        if (strtolower($element->tagName) === 'a') {
            $element->setAttribute('target', '_blank');
            $element->setAttribute('rel', 'noopener noreferrer');
        }

        $children = [];
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $children[] = $child;
            }
        }
        foreach ($children as $child) {
            self::sanitizeTree($child);
        }
    }

    private static function sanitizeCss(string $css): string
    {
        $css = preg_replace('/expression\s*\(/i', 'banned(', $css) ?? $css;
        $css = preg_replace('/-moz-binding/i', 'banned', $css) ?? $css;
        $css = preg_replace('/behavior\s*:/i', 'banned:', $css) ?? $css;
        $css = preg_replace('/@import/i', 'banned', $css) ?? $css;
        $css = preg_replace('/javascript\s*:/i', 'banned:', $css) ?? $css;
        $css = preg_replace('/vbscript\s*:/i', 'banned:', $css) ?? $css;

        return trim($css);
    }

    private static function isSafeUri(string $uri): bool
    {
        $value = trim(html_entity_decode($uri, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($value === '' || $value === '#') {
            return true;
        }
        if (preg_match('/^\s*(javascript|vbscript|data\s*:\s*text)\s*:/i', $value) === 1) {
            return false;
        }
        if (preg_match('~^(https?://|mailto:|tel:|cid:|data:image/|/|\./|#)~i', $value) === 1) {
            return true;
        }

        return preg_match('~^[a-z][a-z0-9+\-.]*:~i', $value) !== 1;
    }
}
