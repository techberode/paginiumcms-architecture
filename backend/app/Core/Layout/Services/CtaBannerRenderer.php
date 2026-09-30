<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Layout\Services;

/**
 * Conditional HTML for bundled cta-banner (Phase 1 slot toggles).
 */
final class CtaBannerRenderer
{
    /**
     * @param array<string, string> $attrs
     */
    public static function render(array $attrs): string
    {
        $title = self::text($attrs['title'] ?? '');
        $subtitle = self::text($attrs['subtitle'] ?? '');
        $cta = self::text($attrs['cta'] ?? '');
        $href = self::text($attrs['href'] ?? '#');
        $tone = self::text($attrs['tone'] ?? 'primary');
        if (!in_array($tone, ['primary', 'muted'], true)) {
            $tone = 'primary';
        }

        $showSubtitle = self::flag($attrs['show-subtitle'] ?? 'true', true);
        $showCta = self::flag($attrs['show-cta'] ?? 'true', true);

        $html = '<section class="pg-cta pg-cta-' . $tone . '"><div class="pg-cta-inner">';
        if ($title !== '') {
            $html .= '<h2 class="pg-cta-title">' . $title . '</h2>';
        }
        if ($showSubtitle && $subtitle !== '') {
            $html .= '<p class="pg-cta-subtitle">' . $subtitle . '</p>';
        }
        if ($showCta && $cta !== '') {
            $html .= '<a class="pg-btn pg-btn-primary pg-cta-link" href="' . $href . '">' . $cta . '</a>';
        }
        $html .= '</div></section>';

        return $html;
    }

    private static function flag(string $value, bool $defaultWhenEmpty): bool
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return $defaultWhenEmpty;
        }

        return filter_var($trimmed, FILTER_VALIDATE_BOOLEAN);
    }

    private static function text(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
