<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Mail\Services;

use PaginiumCMS\Core\Media\Services\DamMediaUrl;

/**
 * Loads allow-listed avatar files for CID embedding in outbound mail signatures.
 */
final class MailSignatureInlineAvatarLoader
{
    public const CONTENT_ID = 'paginium-signature-avatar';

    private const CONTENT_PREFIX = '/storage/app/content/';

    /**
     * @return array{contentId: string, mime: string, bytes: string}|null
     */
    public static function load(string $avatarUrl, string $contentBasePath): ?array
    {
        $relative = self::relativeMediaPath($avatarUrl);
        if ($relative === '') {
            return null;
        }

        $base = realpath(rtrim($contentBasePath, '/\\'));
        if ($base === false) {
            return null;
        }

        $candidate = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $real = realpath($candidate);
        if ($real === false || !is_file($real) || !str_starts_with($real, $base . DIRECTORY_SEPARATOR)) {
            return null;
        }

        if (!self::isAllowedRelativeMedia($relative)) {
            return null;
        }

        $bytes = file_get_contents($real);
        if ($bytes === false || $bytes === '') {
            return null;
        }

        $mime = mime_content_type($real) ?: 'application/octet-stream';
        if (!str_starts_with($mime, 'image/')) {
            return null;
        }

        return [
            'contentId' => self::CONTENT_ID,
            'mime' => $mime,
            'bytes' => $bytes,
        ];
    }

    public static function cidSrc(): string
    {
        return 'cid:' . self::CONTENT_ID;
    }

    /**
     * Replace cid: references with data URLs for local archive / inbox preview.
     *
     * @param list<array{contentId: string, mime: string, bytes: string}> $inlineImages
     */
    public static function embedInHtmlForDisplay(string $html, array $inlineImages): string
    {
        if ($html === '' || $inlineImages === []) {
            return $html;
        }

        foreach ($inlineImages as $image) {
            $data = 'data:' . $image['mime'] . ';base64,' . base64_encode($image['bytes']);
            $cid = strtolower($image['contentId']);
            $html = str_ireplace('cid:' . $cid, $data, $html);
            $local = strstr($cid, '@', true);
            if (is_string($local) && $local !== '' && $local !== $cid) {
                $html = preg_replace(
                    '/\bcid:' . preg_quote($local, '/') . '(?![@a-z0-9._-])/i',
                    $data,
                    $html
                ) ?? $html;
            }
        }

        return $html;
    }

    private static function relativeMediaPath(string $avatarUrl): string
    {
        $avatarUrl = trim($avatarUrl);
        if ($avatarUrl === '') {
            return '';
        }

        if (str_starts_with($avatarUrl, 'http://') || str_starts_with($avatarUrl, 'https://')) {
            $path = (string) parse_url($avatarUrl, PHP_URL_PATH);
            $avatarUrl = $path !== '' ? $path : '';
        }

        if ($avatarUrl === '') {
            return '';
        }

        if (!str_starts_with($avatarUrl, '/')) {
            $avatarUrl = '/' . ltrim($avatarUrl, '/');
        }

        if (!DamMediaUrl::sanitize($avatarUrl)) {
            return '';
        }

        if (str_starts_with($avatarUrl, self::CONTENT_PREFIX)) {
            return ltrim(substr($avatarUrl, strlen(self::CONTENT_PREFIX)), '/');
        }

        if (str_starts_with($avatarUrl, '/api/media/file/')) {
            return ltrim(substr($avatarUrl, strlen('/api/media/file/')), '/');
        }

        return '';
    }

    private static function isAllowedRelativeMedia(string $relative): bool
    {
        $relative = str_replace('\\', '/', $relative);

        return str_starts_with($relative, 'media/');
    }
}
