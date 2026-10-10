<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Layout\Services;

use PaginiumCMS\Core\Media\Services\DamMediaUrl;

/**
 * [before-after] shortcode — DAM image pair with React island slider (It.58f-i remainder).
 */
final class BeforeAfterRenderer
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function attributeSchema(): array
    {
        return [
            'before' => ['type' => 'media', 'accept' => 'image'],
            'after' => ['type' => 'media', 'accept' => 'image'],
            'label-before' => ['type' => 'string'],
            'label-after' => ['type' => 'string'],
        ];
    }

    /**
     * @param array<string, string> $attrs
     */
    public static function render(array $attrs): string
    {
        $before = DamMediaUrl::sanitize($attrs['before'] ?? '');
        $after = DamMediaUrl::sanitize($attrs['after'] ?? '');
        if ($before === '' || $after === '') {
            return '';
        }

        $labelBefore = self::text($attrs['label-before'] ?? '');
        $labelAfter = self::text($attrs['label-after'] ?? '');

        $html = '<section class="pg-island pg-island--before-after pg-before-after" data-island="before-after"';
        $html .= ' data-before="' . self::text($before) . '"';
        $html .= ' data-after="' . self::text($after) . '"';
        if ($labelBefore !== '') {
            $html .= ' data-label-before="' . $labelBefore . '"';
        }
        if ($labelAfter !== '') {
            $html .= ' data-label-after="' . $labelAfter . '"';
        }
        $html .= '></section>';

        return $html;
    }

    private static function text(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
