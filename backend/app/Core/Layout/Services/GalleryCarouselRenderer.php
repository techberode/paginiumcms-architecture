<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Layout\Services;

/**
 * Hydration marker for [gallery-carousel] (Islands Phase D).
 *
 * Public SPA mounts FeatureGallerySlider via gallery-carousel island.
 * Server HTML is an empty section with data-* props only.
 */
final class GalleryCarouselRenderer
{
    /** @var list<string> */
    private const LAYOUTS = ['slider', 'hero-strip'];

    /** @var list<string> */
    private const EFFECTS = ['subtle', 'cinematic', 'minimal'];

    /** @var list<string> */
    private const CAPTION_STYLES = ['below', 'overlay', 'side'];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function attributeSchema(): array
    {
        return [
            'title' => ['type' => 'string'],
            'tag' => ['type' => 'string'],
            'layout' => ['type' => 'enum', 'options' => ['', 'slider', 'hero-strip']],
            'effect' => ['type' => 'enum', 'options' => ['', 'subtle', 'cinematic', 'minimal']],
            'autoplay' => ['type' => 'bool', 'default' => true],
            'modal-caption-style' => ['type' => 'enum', 'options' => ['', 'below', 'overlay', 'side']],
        ];
    }

    /**
     * @param array<string, string> $attrs
     */
    public static function render(array $attrs): string
    {
        $title = self::text($attrs['title'] ?? '');
        $tag = self::text($attrs['tag'] ?? '');
        $layout = self::enum($attrs['layout'] ?? '', self::LAYOUTS, 'slider');
        $effect = self::enum($attrs['effect'] ?? '', self::EFFECTS, 'subtle');
        $autoplay = self::flag($attrs['autoplay'] ?? 'true', true) ? 'true' : 'false';
        $caption = self::enum($attrs['modal-caption-style'] ?? '', self::CAPTION_STYLES, '');

        $html = '<section class="pg-island pg-island--gallery-carousel pg-gallery-carousel"';
        $html .= ' data-island="gallery-carousel"';
        if ($tag !== '') {
            $html .= ' data-tag="' . $tag . '"';
        }
        if ($title !== '') {
            $html .= ' data-title="' . $title . '"';
        }
        $html .= ' data-layout="' . self::text($layout) . '"';
        $html .= ' data-effect="' . self::text($effect) . '"';
        $html .= ' data-autoplay="' . $autoplay . '"';
        if ($caption !== '') {
            $html .= ' data-modal-caption-style="' . self::text($caption) . '"';
        }
        $html .= '></section>';

        return $html;
    }

    /**
     * @param list<string> $allowed
     */
    private static function enum(string $raw, array $allowed, string $default): string
    {
        $value = strtolower(trim($raw));

        return $value !== '' && in_array($value, $allowed, true) ? $value : $default;
    }

    private static function flag(string $raw, bool $default): bool
    {
        $value = strtolower(trim($raw));
        if ($value === '') {
            return $default;
        }

        return !in_array($value, ['0', 'false', 'no', 'off'], true);
    }

    private static function text(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
