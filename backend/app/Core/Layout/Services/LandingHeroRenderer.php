<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Layout\Services;

use PaginiumCMS\Core\Media\Services\DamMediaUrl;

/**
 * Core HTML for landing-hero when DAM image/video attrs are present (It.58f-e).
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
        $href = self::text($attrs['href'] ?? '');

        $image = DamMediaUrl::sanitize($attrs['image'] ?? '');
        $poster = DamMediaUrl::sanitize($attrs['poster'] ?? '');
        $src = DamMediaUrl::sanitize($attrs['src'] ?? '');
        $srcMobile = DamMediaUrl::sanitize($attrs['srcmobile'] ?? '');

        $still = $image !== '' ? $image : $poster;
        if ($poster === '') {
            $poster = $still;
        }

        $html = '<section class="pg-hero">';
        if ($still !== '' || $src !== '') {
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
        if ($subtitle !== '') {
            $html .= '<p class="pg-hero-subtitle">' . $subtitle . '</p>';
        }
        if ($cta !== '') {
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

    private static function text(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
