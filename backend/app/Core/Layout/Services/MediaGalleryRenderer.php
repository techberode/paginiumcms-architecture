<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Layout\Services;

use PaginiumCMS\Core\FlatFile\Models\MediaFile;
use PaginiumCMS\Core\Media\Services\DamMediaUrl;
use PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface;

/**
 * Static HTML for [media-gallery] (It.58f-i-i).
 *
 * Curated DAM images and videos by registry path; public island adds PaginiumMediaGallery lightbox.
 */
final class MediaGalleryRenderer
{
    private const MAX_ITEMS = 48;

    /** @var list<string> */
    private const COLUMNS = ['2', '3', '4'];

    /** @var list<string> */
    private const LAYOUTS = ['grid', 'masonry'];

    /** @var list<string> */
    private const CAPTION_STYLES = ['below', 'overlay', 'side'];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function attributeSchema(): array
    {
        return [
            'title' => ['type' => 'string'],
            'ids' => ['type' => 'string'],
            'columns' => ['type' => 'enum', 'options' => ['', '2', '3', '4']],
            'layout' => ['type' => 'enum', 'options' => ['', 'grid', 'masonry']],
            'modal-caption-style' => ['type' => 'enum', 'options' => ['', 'below', 'overlay', 'side']],
        ];
    }

    /**
     * @param array<string, string> $attrs
     */
    public static function render(array $attrs, ?MediaRepositoryInterface $media): string
    {
        $title = self::text($attrs['title'] ?? '');
        $columns = self::enum($attrs['columns'] ?? '', self::COLUMNS, '3');
        $layout = self::enum($attrs['layout'] ?? '', self::LAYOUTS, 'grid');
        $captionStyle = self::enum($attrs['modal-caption-style'] ?? '', self::CAPTION_STYLES, '');

        $paths = self::parseIds($attrs['ids'] ?? '');
        /** @var list<MediaFile> $files */
        $files = [];
        if ($media !== null) {
            foreach ($paths as $path) {
                $file = $media->findByPath($path);
                if ($file === null) {
                    continue;
                }
                $mime = strtolower($file->getMimeType());
                if (!str_starts_with($mime, 'image/') && !str_starts_with($mime, 'video/')) {
                    continue;
                }
                if (DamMediaUrl::sanitize($file->getUrl()) === '') {
                    continue;
                }
                $files[] = $file;
                if (count($files) >= self::MAX_ITEMS) {
                    break;
                }
            }
        }

        $html = '<section class="pg-island pg-island--media-gallery pg-media-gallery"';
        $html .= ' data-island="media-gallery"';
        if ($title !== '') {
            $html .= ' data-title="' . $title . '"';
        }
        $html .= ' data-columns="' . self::text($columns) . '"';
        $html .= ' data-layout="' . self::text($layout) . '"';
        if ($captionStyle !== '') {
            $html .= ' data-modal-caption-style="' . self::text($captionStyle) . '"';
        }
        $html .= '>';

        if ($title !== '') {
            $html .= '<h2 class="pg-media-gallery-title">' . $title . '</h2>';
        }

        if ($files === []) {
            $html .= '<p class="pg-media-gallery-empty">No gallery media.</p></section>';

            return $html;
        }

        $gridClass = 'pg-media-gallery-grid pg-media-gallery-cols-' . self::text($columns);
        if ($layout === 'masonry') {
            $gridClass .= ' pg-media-gallery-grid--masonry';
        }

        $html .= '<div class="' . $gridClass . '">';
        foreach ($files as $index => $file) {
            $src = self::text(DamMediaUrl::sanitize($file->getUrl()));
            $alt = self::text(self::imageAlt($file));
            $caption = self::text(trim($file->getTitle()));
            $path = self::text($file->getPath());
            $mimeRaw = strtolower($file->getMimeType());
            $isVideo = str_starts_with($mimeRaw, 'video/');

            $html .= '<article class="pg-media-gallery-item" data-index="' . $index . '"';
            $html .= ' data-media-path="' . $path . '"';
            if ($isVideo) {
                $html .= ' data-media-type="video" data-mime-type="' . self::text($mimeRaw) . '"';
            }
            $html .= '>';
            if ($isVideo) {
                $html .= '<video class="pg-media-gallery-video" src="' . $src . '"';
                $html .= ' muted playsinline preload="metadata"';
                $html .= ' aria-label="' . $alt . '"></video>';
            } else {
                $html .= '<img class="pg-media-gallery-image" src="' . $src . '" alt="' . $alt . '" loading="lazy" decoding="async">';
            }
            if ($caption !== '') {
                $html .= '<span class="pg-media-gallery-item-caption">' . $caption . '</span>';
            }
            $html .= '</article>';
        }
        $html .= '</div></section>';

        return $html;
    }

    /**
     * @return list<string>
     */
    public static function parseIds(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/[|,]+/', $raw) ?: [];
        $paths = [];
        foreach ($parts as $part) {
            $path = trim($part);
            if ($path === '' || str_contains($path, '..') || str_contains($path, "\0")) {
                continue;
            }
            if (str_starts_with($path, '/')) {
                continue;
            }
            if (preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._\\/-]*$/', $path) !== 1) {
                continue;
            }
            $paths[] = $path;
            if (count($paths) >= self::MAX_ITEMS) {
                break;
            }
        }

        return $paths;
    }

    private static function imageAlt(MediaFile $file): string
    {
        $alt = trim($file->getAltText());
        if ($alt !== '') {
            return $alt;
        }

        $title = trim($file->getTitle());
        if ($title !== '') {
            return $title;
        }

        return trim($file->getFileName());
    }

    /**
     * @param list<string> $allowed
     */
    private static function enum(string $raw, array $allowed, string $default): string
    {
        $value = strtolower(trim($raw));

        return $value !== '' && in_array($value, $allowed, true) ? $value : $default;
    }

    private static function text(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
