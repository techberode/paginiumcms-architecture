<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Layout\Services;

/**
 * KPI stats row (It.58f-i-f — optional count-up island).
 */
final class StatsRowRenderer
{
    /** @var list<string> */
    private const ANIMATIONS = ['count-up'];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function attributeSchema(): array
    {
        return [
            'animate' => ['type' => 'enum', 'options' => ['', 'count-up']],
        ];
    }

    /**
     * @param array<string, string> $attrs
     */
    public static function render(array $attrs, string $innerHtml): string
    {
        $animate = self::enum($attrs['animate'] ?? '', self::ANIMATIONS, '');

        if ($animate !== 'count-up') {
            return '<div class="pg-stats pg-reveal">' . $innerHtml . '</div>';
        }

        $html = '<section class="pg-island pg-island--stats-row pg-stats pg-stats--count-up pg-reveal"';
        $html .= ' data-island="stats-row" data-animate="count-up">';
        $html .= $innerHtml;
        $html .= '</section>';

        return $html;
    }

    /**
     * @param list<string> $allowed
     */
    private static function enum(string $raw, array $allowed, string $default): string
    {
        $value = strtolower(trim($raw));
        if ($value === '') {
            return $default;
        }

        return in_array($value, $allowed, true) ? $value : $default;
    }
}
