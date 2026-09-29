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
            '/:::video\s*\n\s*src:\s*(\S+)(?:\n\s*poster:\s*(\S+))?(?:\n\s*caption:\s*(.+))?\s*\n\s*:::/s',
            function (array $matches): string {
                $poster = isset($matches[2]) ? (string) $matches[2] : '';
                $caption = isset($matches[3]) ? trim((string) $matches[3]) : '';

                return $this->renderMatch((string) $matches[1], $poster, $caption);
            },
            $markdown
        );

        if (!is_string($expanded)) {
            return $markdown;
        }

        $oneLine = preg_replace_callback(
            '/:::video\s+src="([^"]+)"(?:\s+poster="([^"]+)")?(?:\s+caption="([^"]*)")?\s*:::/',
            function (array $matches): string {
                $poster = isset($matches[2]) ? (string) $matches[2] : '';
                $caption = isset($matches[3]) ? trim((string) $matches[3]) : '';

                return $this->renderMatch((string) $matches[1], $poster, $caption);
            },
            $expanded
        );

        return is_string($oneLine) ? $oneLine : $expanded;
    }

    private function renderMatch(string $srcRaw, string $posterRaw = '', string $captionRaw = ''): string
    {
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

        return '<figure class="paginium-figure paginium-figure--video">' . $video
            . '<figcaption>' . $safeCaption . '</figcaption></figure>';
    }

    public function sanitizeMediaUrl(string $url): string
    {
        return DamMediaUrl::sanitize($url);
    }
}
