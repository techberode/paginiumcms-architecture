<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Layout\Services;

use PaginiumCMS\Core\Media\Services\DamMediaUrl;

/**
 * Core HTML for landing-hero (It.58f-e + Phase 1 slot toggles).
 *
 * Video is muted + looping + playsinline. Never controls. Never autoplay with sound.
 */
final class LandingHeroRenderer
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

        $showSubtitle = self::flag($attrs['show-subtitle'] ?? 'true', true);
        $showCta = self::flag($attrs['show-cta'] ?? 'true', true);
        $showMedia = self::flag($attrs['show-media'] ?? 'true', true);

        $image = DamMediaUrl::sanitize($attrs['image'] ?? '');
        $poster = DamMediaUrl::sanitize($attrs['poster'] ?? '');
        $src = DamMediaUrl::sanitize($attrs['src'] ?? '');
        $srcMobile = DamMediaUrl::sanitize($attrs['srcmobile'] ?? '');

        $still = $image !== '' ? $image : $poster;
        if ($poster === '') {
            $poster = $still;
        }

        $html = '<section class="pg-hero">';
        if ($showMedia && ($still !== '' || $src !== '')) {
            $html .= '<div class="pg-hero-media">';
            if ($still !== '') {
                $html .= '<img class="pg-hero-photo" src="' . self::text($still) . '" alt="" decoding="async">';
            }
            if ($src !== '') {
                $html .= '<video class="pg-hero-video" muted loop playsinline autoplay preload="metadata"';
                if ($poster !== '') {
                    $html .= ' poster="' . self::text($poster) . '"';
                }
                $html .= '>';
                if ($srcMobile !== '') {
                    $html .= '<source src="' . self::text($srcMobile) . '" media="(max-width: 767px)">';
                }
                $html .= '<source src="' . self::text($src) . '">';
                $html .= '</video>';
            }
            $html .= '</div>';
        }

        $html .= '<div class="pg-hero-inner">';
        if ($title !== '') {
            $html .= '<h1 class="pg-hero-title">' . $title . '</h1>';
        }
        if ($showSubtitle && $subtitle !== '') {
            $html .= '<p class="pg-hero-subtitle">' . $subtitle . '</p>';
        }
        if ($showCta && $cta !== '') {
            $html .= '<a class="pg-btn pg-btn-primary" href="' . $href . '">' . $cta . '</a>';
        }
        $html .= '</div></section>';

        return $html;
    }

    /**
     * @param array<string, string> $attrs
     */
    public static function hasMedia(array $attrs): bool
    {
        return DamMediaUrl::sanitize($attrs['image'] ?? '') !== ''
            || DamMediaUrl::sanitize($attrs['poster'] ?? '') !== ''
            || DamMediaUrl::sanitize($attrs['src'] ?? '') !== ''
            || DamMediaUrl::sanitize($attrs['srcmobile'] ?? '') !== '';
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
