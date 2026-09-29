<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Editor\Services;

use PaginiumCMS\Core\Media\Services\DamMediaUrl;

/**
 * Expands guarded :::video Markdown blocks to safe HTML (It.79).
 */
final class VideoEmbedShortcode
{
    public function expand(string $markdown): string
    {
        $expanded = preg_replace_callback(
            '/:::video\s*\n\s*src:\s*(\S+)(?:\n\s*poster:\s*(\S+))?(?:\n\s*caption:\s*(.+?))?(?:\n\s*captionPosition:\s*(above|below))?\s*\n\s*:::/s',
            function (array $matches): string {
                $poster = isset($matches[2]) ? (string) $matches[2] : '';
                $caption = isset($matches[3]) ? trim((string) $matches[3]) : '';
                $captionPosition = isset($matches[4]) ? (string) $matches[4] : 'below';

                return $this->renderMatch((string) $matches[1], $poster, $caption, $captionPosition);
            },
            $markdown
        );

        if (!is_string($expanded)) {
            return $markdown;
        }

        $oneLine = preg_replace_callback(
            '/:::video\s+src="([^"]+)"(?:\s+poster="([^"]+)")?(?:\s+caption="([^"]*)")?(?:\s+captionPosition="(above|below)")?\s*:::/',
            function (array $matches): string {
                $poster = isset($matches[2]) ? (string) $matches[2] : '';
                $caption = isset($matches[3]) ? trim((string) $matches[3]) : '';
                $captionPosition = isset($matches[4]) ? (string) $matches[4] : 'below';

                return $this->renderMatch((string) $matches[1], $poster, $caption, $captionPosition);
            },
            $expanded
        );

        return is_string($oneLine) ? $oneLine : $expanded;
    }

    private function renderMatch(
        string $srcRaw,
        string $posterRaw = '',
        string $captionRaw = '',
        string $captionPositionRaw = 'below'
    ): string {
        $src = $this->sanitizeMediaUrl($srcRaw);
        if ($src === '') {
            return '';
        }

        $poster = $this->sanitizeMediaUrl($posterRaw);
        $attrs = ' controls playsinline preload="metadata"';
        if ($poster !== '') {
            $attrs .= ' poster="' . htmlspecialchars($poster, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
        }

        $video = '<video src="' . htmlspecialchars($src, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"' . $attrs . '></video>';
        $caption = trim($captionRaw);
        if ($caption === '') {
            return $video;
        }

        $safeCaption = htmlspecialchars($caption, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $above = strtolower(trim($captionPositionRaw)) === 'above';
        $figureClass = 'paginium-figure paginium-figure--video' . ($above ? ' paginium-figure--caption-top' : '');
        $cap = '<figcaption>' . $safeCaption . '</figcaption>';

        return '<figure class="' . $figureClass . '">' . ($above ? $cap . $video : $video . $cap) . '</figure>';
    }

    public function sanitizeMediaUrl(string $url): string
    {
        return DamMediaUrl::sanitize($url);
    }
}
