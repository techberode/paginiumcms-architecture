<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Layout\Services;

/**
 * Conditional HTML for bundled showcase-hero (Phase 1 slot toggles).
 */
final class ShowcaseHeroRenderer
{
    /**
     * @param array<string, string> $attrs
     */
    public static function render(array $attrs): string
    {
        $badge = self::text($attrs['badge'] ?? '');
        $title = self::text($attrs['title'] ?? '');
        $subtitle = self::text($attrs['subtitle'] ?? '');
        $terminal = self::text($attrs['terminal'] ?? '');
        $cta = self::text($attrs['cta'] ?? '');
        $href = self::text($attrs['href'] ?? '#');
        $cta2 = self::text($attrs['cta2'] ?? '');
        $href2 = self::text($attrs['href2'] ?? '#');

        $showBadge = self::flag($attrs['show-badge'] ?? 'true', true);
        $showTerminal = self::flag($attrs['show-terminal'] ?? 'true', true);
        $showCta = self::flag($attrs['show-cta'] ?? 'true', true);
        $showCta2 = self::flag($attrs['show-cta2'] ?? 'true', true);

        $html = '<section class="pg-showcase-hero"><div class="pg-showcase-hero-inner">';

        if ($showBadge && $badge !== '') {
            $html .= '<p class="pg-showcase-badge">' . $badge . '</p>';
        }
        if ($title !== '') {
            $html .= '<h1 class="pg-showcase-title">' . $title . '</h1>';
        }
        if ($subtitle !== '') {
            $html .= '<p class="pg-showcase-subtitle">' . $subtitle . '</p>';
        }
        if ($showTerminal && $terminal !== '') {
            $html .= '<pre class="pg-showcase-terminal"><code>$ ' . $terminal . '</code></pre>';
        }

        $actions = '';
        if ($showCta && $cta !== '') {
            $actions .= '<a class="pg-btn pg-btn-primary" href="' . $href . '">' . $cta . '</a>';
        }
        if ($showCta2 && $cta2 !== '') {
            $actions .= '<a class="pg-btn pg-btn-ghost" href="' . $href2 . '">' . $cta2 . '</a>';
        }
        if ($actions !== '') {
            $html .= '<div class="pg-showcase-actions">' . $actions . '</div>';
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
