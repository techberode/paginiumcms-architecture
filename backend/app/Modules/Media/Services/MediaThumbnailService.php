<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Services;

/**
 * On-demand raster image thumbnails cached alongside media (It.84 perf).
 */
final class MediaThumbnailService
{
    private const MIN_WIDTH = 64;

    private const MAX_WIDTH = 1920;

    private const THUMB_SUBDIR = '.thumbs';

    /** @var list<string> */
    private const RASTER_MIMES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    public static function isSupportedRasterMime(string $mime): bool
    {
        return in_array(strtolower($mime), self::RASTER_MIMES, true);
    }

    public function isAvailable(): bool
    {
        return extension_loaded('gd') && function_exists('imagecreatetruecolor');
    }

    /**
     * Returns absolute path to a cached thumbnail, or null when generation fails.
     */
    public function ensure(string $sourcePath, int $maxWidth): ?string
    {
        if (!$this->isAvailable() || !is_file($sourcePath)) {
            return null;
        }

        $maxWidth = max(self::MIN_WIDTH, min(self::MAX_WIDTH, $maxWidth));

        $mime = mime_content_type($sourcePath) ?: '';
        if (!self::isSupportedRasterMime($mime)) {
            return null;
        }

        $dimensions = @getimagesize($sourcePath);
        if ($dimensions === false) {
            return null;
        }

        $srcW = (int) $dimensions[0];
        if ($srcW <= $maxWidth) {
            return null;
        }

        $sourceMtime = filemtime($sourcePath);
        if ($sourceMtime === false) {
            return null;
        }

        $thumbPath = $this->thumbPathFor($sourcePath, $maxWidth);
        if (
            is_file($thumbPath)
            && filemtime($thumbPath) !== false
            && filemtime($thumbPath) >= $sourceMtime
        ) {
            return $thumbPath;
        }

        $thumbDir = dirname($thumbPath);
        if (!is_dir($thumbDir) && !mkdir($thumbDir, 0755, true) && !is_dir($thumbDir)) {
            return null;
        }

        return $this->generate($sourcePath, $thumbPath, $maxWidth, $mime) ? $thumbPath : null;
    }

    private function thumbPathFor(string $sourcePath, int $maxWidth): string
    {
        $dir = dirname($sourcePath) . DIRECTORY_SEPARATOR . self::THUMB_SUBDIR;
        $hash = hash('sha256', $sourcePath . '|' . $maxWidth);

        return $dir . DIRECTORY_SEPARATOR . substr($hash, 0, 32) . '_w' . $maxWidth . '.webp';
    }

    private function generate(string $sourcePath, string $thumbPath, int $maxWidth, string $mime): bool
    {
        $source = $this->loadImage($sourcePath, $mime);
        if ($source === false) {
            return false;
        }

        $srcW = imagesx($source);
        $srcH = imagesy($source);

        if ($srcW <= $maxWidth) {
            imagedestroy($source);

            return false;
        }

        $targetW = max(1, $maxWidth);
        $targetH = max(1, (int) round($srcH * ($maxWidth / $srcW)));

        $target = imagecreatetruecolor($targetW, $targetH);
        if ($target === false) {
            imagedestroy($source);

            return false;
        }

        imagealphablending($target, false);
        imagesavealpha($target, true);

        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetW, $targetH, $srcW, $srcH);
        imagedestroy($source);

        $saved = function_exists('imagewebp')
            ? imagewebp($target, $thumbPath, 82)
            : imagejpeg($target, $thumbPath, 82);

        imagedestroy($target);

        return $saved;
    }

    /**
     * @return \GdImage|false
     */
    private function loadImage(string $path, string $mime)
    {
        return match (strtolower($mime)) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            'image/gif' => @imagecreatefromgif($path),
            default => false,
        };
    }
}
