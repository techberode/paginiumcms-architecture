<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security;

/**
 * Trait pre validáciu a sanitizáciu vstupov.
 */
trait ValidationTrait
{
    /**
     * Validácia emailu.
     */
    protected function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validácia URL.
     */
    protected function validateUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validácia IP adresy.
     */
    protected function validateIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Validácia boolean hodnoty.
     */
    protected function validateBoolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) !== false;
    }

    /**
     * Validácia celého čísla.
     */
    protected function validateInt(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Validácia float.
     */
    protected function validateFloat(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
    }

    /**
     * Validácia reťazca (dĺžka, znaky).
     */
    protected function validateString(
        string $value,
        int $minLength = 0,
        int $maxLength = PHP_INT_MAX,
        string $pattern = null
    ): bool {
        $length = mb_strlen($value);
        
        if ($length < $minLength || $length > $maxLength) {
            return false;
        }

        if ($pattern !== null && !preg_match($pattern, $value)) {
            return false;
        }

        return true;
    }

    /**
     * Sanitizácia reťazca.
     */
    protected function sanitizeString(string $value): string
    {
        // Odstránenie HTML tagov
        $value = strip_tags($value);

        // Odstránenie nežiaducich znakov
        $value = preg_replace('/[^\p{L}\p{N}\s\-_.]/u', '', $value);

        return trim($value);
    }

    /**
     * Sanitizácia HTML.
     */
    protected function sanitizeHtml(string $html): string
    {
        // Povolené tagy a atribúty
        $allowedTags = [
            'p' => ['class'],
            'h1' => ['class'],
            'h2' => ['class'],
            'h3' => ['class'],
            'h4' => ['class'],
            'h5' => ['class'],
            'h6' => ['class'],
            'ul' => ['class'],
            'ol' => ['class'],
            'li' => ['class'],
            'strong' => [],
            'em' => [],
            'b' => [],
            'i' => [],
            'u' => [],
            'a' => ['href', 'target', 'rel', 'class'],
            'img' => ['src', 'alt', 'width', 'height', 'class'],
            'blockquote' => ['class'],
            'code' => ['class'],
            'pre' => ['class'],
            'br' => [],
            'hr' => [],
            'table' => ['class'],
            'thead' => ['class'],
            'tbody' => ['class'],
            'tr' => ['class'],
            'td' => ['class', 'colspan', 'rowspan'],
            'th' => ['class', 'colspan', 'rowspan'],
        ];

        // Použijeme DOMDocument na sanitizáciu
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $this->sanitizeNode($dom, $allowedTags);

        return $dom->saveHTML();
    }

    private function sanitizeNode(\DOMNode $node, array $allowedTags): void
    {
        if ($node->nodeType === XML_ELEMENT_NODE) {
            $tagName = $node->nodeName;

            // Odstránenie neznámych tagov
            if (!isset($allowedTags[$tagName])) {
                $parent = $node->parentNode;
                while ($node->firstChild) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);
                return;
            }

            // Odstránenie neplatných atribútov
            $allowedAttrs = $allowedTags[$tagName];
            $attrsToRemove = [];
            foreach ($node->attributes as $attr) {
                if (!in_array($attr->name, $allowedAttrs, true)) {
                    $attrsToRemove[] = $attr->name;
                }
            }
            foreach ($attrsToRemove as $attrName) {
                $node->removeAttribute($attrName);
            }

            // Sanitizácia URL atribútov
            if ($tagName === 'a') {
                $href = $node->getAttribute('href');
                if (!empty($href) && !$this->validateUrl($href)) {
                    $node->removeAttribute('href');
                }
            }

            if ($tagName === 'img') {
                $src = $node->getAttribute('src');
                if (!empty($src) && !$this->validateUrl($src)) {
                    $node->removeAttribute('src');
                }
            }
        }

        // Rekurzívne spracovanie detí
        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            $this->sanitizeNode($child, $allowedTags);
        }
    }
}
