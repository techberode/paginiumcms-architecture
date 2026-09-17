<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Layout\Services;

use PaginiumCMS\Core\Media\Services\DamMediaUrl;
use PaginiumCMS\Modules\Gallery\Models\GalleryItem;

/**
 * Static HTML for [feature-gallery] (It.58f-f).
 *
 * Reads the existing It.65 gallery store — no second catalog.
 * Public SPA hydrates the same markup with FeatureGallerySection.
 */
final class FeatureGalleryRenderer
{
    /**
     * @param array<string, string> $attrs
     * @param list<GalleryItem> $items
     */
    public static function render(array $attrs, array $items): string
    {
        $title = self::text($attrs['title'] ?? '');
        $tag = self::text($attrs['tag'] ?? '');
        $pin = trim($attrs['tag'] ?? '');

        $html = '<section class="pg-feature-gallery"';
        if ($tag !== '') {
            $html .= ' data-tag="' . $tag . '"';
        }
        if ($title !== '') {
            $html .= ' data-title="' . $title . '"';
        }
        $html .= '>';

        if ($title !== '') {
            $html .= '<h2 class="pg-feature-gallery-title">' . $title . '</h2>';
        }

        /** @var list<GalleryItem> $visible */
        $visible = [];
        foreach ($items as $item) {
            if ($pin !== '' && $item->getFeatureTag() !== $pin) {
                continue;
            }
            if (DamMediaUrl::sanitize($item->getMediaPath()) === '') {
                continue;
            }
            $visible[] = $item;
        }

        if ($visible === []) {
            $html .= '<p class="pg-feature-gallery-empty">No published gallery items.</p></section>';

            return $html;
        }

        $html .= '<div class="pg-feature-gallery-grid">';
        foreach ($visible as $item) {
            $src = self::text(DamMediaUrl::sanitize($item->getMediaPath()));
            $itemTitle = self::text($item->getTitle());
            $description = self::text($item->getDescription());
            $featureTag = self::text((string) ($item->getFeatureTag() ?? ''));
            $href = self::safeHref((string) ($item->getLinkUrl() ?? ''));

            $html .= '<article class="pg-feature-gallery-item">';
            $img = '<img class="pg-feature-gallery-image" src="' . $src . '" alt="' . $itemTitle . '" loading="lazy" decoding="async">';
            if ($href !== '') {
                $html .= '<a href="' . $href . '">' . $img . '</a>';
            } else {
                $html .= $img;
            }
            if ($itemTitle !== '') {
                $html .= '<h3 class="pg-feature-gallery-item-title">' . $itemTitle . '</h3>';
            }
            if ($description !== '') {
                $html .= '<p class="pg-feature-gallery-item-body">' . $description . '</p>';
            }
            if ($featureTag !== '') {
                $html .= '<span class="pg-feature-gallery-tag">' . $featureTag . '</span>';
            }
            $html .= '</article>';
        }
        $html .= '</div></section>';

        return $html;
    }

    private static function text(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function safeHref(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (preg_match('/^\s*(javascript|vbscript|data)\s*:/i', $url) === 1) {
            return '';
        }

        if (preg_match('~^(https?://|/|#)~i', $url) !== 1) {
            return '';
        }

        return self::text($url);
    }
}
