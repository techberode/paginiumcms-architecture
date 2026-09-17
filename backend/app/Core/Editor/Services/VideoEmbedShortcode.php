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
            '/:::video\s*\n\s*src:\s*(\S+)(?:\n\s*poster:\s*(\S+))?\s*\n\s*:::/',
            function (array $matches): string {
                $poster = isset($matches[2]) ? (string) $matches[2] : '';

                return $this->renderMatch((string) $matches[1], $poster);
            },
            $markdown
        );

        if (!is_string($expanded)) {
            return $markdown;
        }

        $oneLine = preg_replace_callback(
            '/:::video\s+src="([^"]+)"(?:\s+poster="([^"]+)")?\s*:::/',
            function (array $matches): string {
                $poster = isset($matches[2]) ? (string) $matches[2] : '';

                return $this->renderMatch((string) $matches[1], $poster);
            },
            $expanded
        );

        return is_string($oneLine) ? $oneLine : $expanded;
    }

    private function renderMatch(string $srcRaw, string $posterRaw = ''): string
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

        return '<video src="' . htmlspecialchars($src, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"' . $attrs . '></video>';
    }

    public function sanitizeMediaUrl(string $url): string
    {
        return DamMediaUrl::sanitize($url);
    }
}
