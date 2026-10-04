<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Layout\Services;

use PaginiumCMS\Core\Media\Services\DamMediaUrl;

/**
 * Section shell for landing/portfolio pages (It.58f-i-b).
 *
 * Wraps expanded inner Markdown/shortcodes with allow-listed pg-section-band chrome.
 */
final class SectionBandRenderer
{
    /** @var list<string> */
    private const OVERLAYS = ['none', 'light', 'dark', 'primary'];

    /** @var list<string> */
    private const RADII = ['sharp', 'rounded', 'pill'];

    /** @var list<string> */
    private const BG_ATTACHMENTS = ['scroll', 'fixed'];

    /** @var list<string> */
    private const LAYOUTS = ['full', 'contained', 'two-column'];

    /** @var list<string> */
    private const TONES = ['none', 'muted', 'accent'];

    /** @var list<string> */
    private const REVEALS = ['none', 'scroll'];

    /** @var list<string> */
    private const HOVER_EFFECTS = ['none', 'lift', 'glow'];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function attributeSchema(): array
    {
        return [
            'anchor' => ['type' => 'string'],
            'bg-image' => ['type' => 'media', 'accept' => 'image'],
            'overlay' => ['type' => 'enum', 'options' => ['', 'none', 'light', 'dark', 'primary']],
            'radius' => ['type' => 'enum', 'options' => ['', 'sharp', 'rounded', 'pill']],
            'bg-attachment' => ['type' => 'enum', 'options' => ['', 'scroll', 'fixed']],
            'layout' => ['type' => 'enum', 'options' => ['', 'full', 'contained', 'two-column']],
            'tone' => ['type' => 'enum', 'options' => ['', 'none', 'muted', 'accent']],
            'reveal' => ['type' => 'enum', 'options' => ['', 'scroll', 'none']],
            'hover-effect' => ['type' => 'enum', 'options' => ['', 'none', 'lift', 'glow']],
        ];
    }

    /**
     * @param array<string, string> $attrs
     */
    public static function render(array $attrs, string $innerHtml): string
    {
        $anchor = self::anchorId($attrs['anchor'] ?? '');
        $bgImage = DamMediaUrl::sanitize($attrs['bg-image'] ?? '');
        $overlay = self::enum($attrs['overlay'] ?? '', self::OVERLAYS, 'none');
        $radius = self::enum($attrs['radius'] ?? '', self::RADII, 'sharp');
        $bgAttachment = self::enum($attrs['bg-attachment'] ?? '', self::BG_ATTACHMENTS, 'scroll');
        $layout = self::enum($attrs['layout'] ?? '', self::LAYOUTS, 'contained');
        $tone = self::enum($attrs['tone'] ?? '', self::TONES, 'none');
        $reveal = self::enum($attrs['reveal'] ?? '', self::REVEALS, 'scroll');
        $hoverEffect = self::enum($attrs['hover-effect'] ?? '', self::HOVER_EFFECTS, 'none');

        $classes = ['pg-section-band'];
        if ($reveal === 'scroll') {
            $classes[] = 'pg-reveal';
        }
        if ($hoverEffect !== 'none') {
            $classes[] = 'pg-section-band--hover-' . $hoverEffect;
        }
        if ($radius !== 'sharp') {
            $classes[] = 'pg-section-band--radius-' . $radius;
        }
        if ($layout !== 'contained') {
            $classes[] = 'pg-section-band--layout-' . $layout;
        }
        if ($bgAttachment === 'fixed') {
            $classes[] = 'pg-section-band--bg-fixed';
        }
        if ($bgImage === '' && $tone !== 'none') {
            $classes[] = 'pg-section-band--tone-' . $tone;
        }
        if ($bgImage !== '' && $overlay !== 'none') {
            $classes[] = 'pg-section-band--overlay-' . $overlay;
        }

        $classAttr = self::text(implode(' ', $classes));
        $html = '<section class="' . $classAttr . '"';
        if ($anchor !== '') {
            $html .= ' id="' . self::text($anchor) . '"';
        }
        $html .= '>';

        if ($bgImage !== '') {
            $html .= '<div class="pg-section-band__media" aria-hidden="true">';
            $html .= '<img class="pg-section-band__image" src="' . self::text($bgImage) . '" alt="" loading="lazy" decoding="async">';
            $html .= '</div>';
            if ($overlay !== 'none') {
                $html .= '<div class="pg-section-band__scrim" aria-hidden="true"></div>';
            }
        }

        $innerClass = 'pg-section-band__inner paginium-prose pg-shortcode-surface';
        if ($layout === 'two-column') {
            $innerClass .= ' pg-section-band__inner--two-column';
        }

        $html .= '<div class="' . $innerClass . '">' . $innerHtml . '</div></section>';

        return $html;
    }

    private static function anchorId(string $raw): string
    {
        $id = trim($raw);
        if ($id === '' || !preg_match('/^[a-zA-Z][a-zA-Z0-9_-]{0,63}$/', $id)) {
            return '';
        }

        return $id;
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

    private static function text(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
