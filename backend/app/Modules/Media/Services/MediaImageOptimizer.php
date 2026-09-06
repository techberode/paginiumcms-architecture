<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Services;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Support\Lang;

/**
 * Re-encodes and optionally downscales raster images via GD.
 */
final class MediaImageOptimizer
{
    public const JPEG_QUALITY = 82;

    public const WEBP_QUALITY = 82;

    public const PNG_COMPRESSION = 9;

    /** @var list<string> */
    private const OPTIMIZABLE_MIMES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
    ];

    public function supportsMime(string $mimeType): bool
    {
        return in_array(strtolower(trim($mimeType)), self::OPTIMIZABLE_MIMES, true);
    }

    public static function isAvailable(): bool
    {
        return extension_loaded('gd') && function_exists('imagecreatetruecolor');
    }

    /**
     * @return array{
     *     available: bool,
     *     jpeg: bool,
     *     png: bool,
     *     webp: bool
     * }
     */
    public static function capabilities(): array
    {
        if (!self::isAvailable()) {
            return [
                'available' => false,
                'jpeg' => false,
                'png' => false,
                'webp' => false,
            ];
        }

        $info = gd_info();

        return [
            'available' => true,
            'jpeg' => !empty($info['JPEG Support']),
            'png' => !empty($info['PNG Support']),
            'webp' => function_exists('imagewebp') && !empty($info['WebP Support']),
        ];
    }

    /**
     * @return array{width: int, height: int, mimeType: string}
     */
    public function inspect(string $binary): array
    {
        if ($binary === '') {
            throw new FlatFileException(Lang::get('optimize_empty', [], 'media'));
        }

        $info = @getimagesizefromstring($binary);
        if ($info === false) {
            throw new FlatFileException(Lang::get('optimize_invalid', [], 'media'));
        }

        $width = $info[0];
        $height = $info[1];
        if ($width < 1 || $height < 1) {
            throw new FlatFileException(Lang::get('optimize_invalid_dimensions', [], 'media'));
        }

        return [
            'width' => $width,
            'height' => $height,
            'mimeType' => $this->normalizeMime($this->detectMime($info)),
        ];
    }

    /**
     * @return array{
     *     binary: string,
     *     mimeType: string,
     *     beforeBytes: int,
     *     afterBytes: int,
     *     savedBytes: int,
     *     savedPercent: float,
     *     beforeWidth: int,
     *     beforeHeight: int,
     *     width: int,
     *     height: int
     * }
     */
    public function optimize(
        string $binary,
        string $mimeType,
        ?int $targetWidth = null,
        ?int $targetHeight = null,
    ): array {
        $this->assertGdAvailable();

        if ($binary === '') {
            throw new FlatFileException(Lang::get('optimize_empty', [], 'media'));
        }

        $beforeBytes = strlen($binary);
        $info = @getimagesizefromstring($binary);
        if ($info === false) {
            throw new FlatFileException(Lang::get('optimize_invalid', [], 'media'));
        }

        $beforeWidth = $info[0];
        $beforeHeight = $info[1];
        if ($beforeWidth < 1 || $beforeHeight < 1) {
            throw new FlatFileException(Lang::get('optimize_invalid_dimensions', [], 'media'));
        }

        $detectedMime = $this->detectMime($info);
        if (!$this->supportsMime($detectedMime)) {
            throw new FlatFileException(Lang::get('optimize_unsupported_type', [], 'media'));
        }

        $this->assertFormatSupported($detectedMime);

        [$targetW, $targetH] = $this->resolveTargetDimensions(
            $beforeWidth,
            $beforeHeight,
            $targetWidth,
            $targetHeight
        );

        if ($targetW < 1 || $targetH < 1) {
            throw new FlatFileException(Lang::get('optimize_invalid_dimensions', [], 'media'));
        }

        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            throw new FlatFileException(Lang::get('optimize_decode_failed', [], 'media'));
        }

        try {
            $encoded = $this->renderImage(
                $source,
                $beforeWidth,
                $beforeHeight,
                $targetW,
                $targetH,
                $detectedMime
            );
        } finally {
            imagedestroy($source);
        }

        if ($encoded === '') {
            throw new FlatFileException(Lang::get('optimize_encode_failed', [], 'media'));
        }

        $afterBytes = strlen($encoded);
        $resized = $targetW < $beforeWidth || $targetH < $beforeHeight;

        if (!$resized && $afterBytes >= $beforeBytes) {
            throw new FlatFileException(Lang::get('optimize_no_reduction', [], 'media'));
        }

        if ($resized && $afterBytes >= $beforeBytes) {
            throw new FlatFileException(Lang::get('optimize_no_reduction', [], 'media'));
        }

        $savedBytes = max(0, $beforeBytes - $afterBytes);
        $savedPercent = round(($savedBytes / $beforeBytes) * 100, 1);

        return [
            'binary' => $encoded,
            'mimeType' => $this->normalizeMime($detectedMime),
            'beforeBytes' => $beforeBytes,
            'afterBytes' => $afterBytes,
            'savedBytes' => $savedBytes,
            'savedPercent' => $savedPercent,
            'beforeWidth' => $beforeWidth,
            'beforeHeight' => $beforeHeight,
            'width' => $targetW,
            'height' => $targetH,
        ];
    }

    /**
     * @return array{int, int}
     */
    private function resolveTargetDimensions(
        int $width,
        int $height,
        ?int $targetWidth,
        ?int $targetHeight,
    ): array {
        $widthOnly = $targetWidth !== null && $targetWidth > 0
            && ($targetHeight === null || $targetHeight <= 0);
        $heightOnly = $targetHeight !== null && $targetHeight > 0
            && ($targetWidth === null || $targetWidth <= 0);

        if (!$widthOnly && !$heightOnly
            && ($targetWidth === null || $targetWidth <= 0)
            && ($targetHeight === null || $targetHeight <= 0)) {
            return [$width, $height];
        }

        if ($widthOnly) {
            if ($targetWidth >= $width) {
                return [$width, $height];
            }

            $scale = $targetWidth / $width;

            return [
                $targetWidth,
                max(1, (int) round($height * $scale)),
            ];
        }

        if ($heightOnly) {
            if ($targetHeight >= $height) {
                return [$width, $height];
            }

            $scale = $targetHeight / $height;

            return [
                max(1, (int) round($width * $scale)),
                $targetHeight,
            ];
        }

        $resolvedWidth = $targetWidth ?? $width;
        $resolvedHeight = $targetHeight ?? $height;
        $scaleW = $resolvedWidth / $width;
        $scaleH = $resolvedHeight / $height;
        $scale = min($scaleW, $scaleH, 1.0);

        if ($scale >= 1.0) {
            return [$width, $height];
        }

        return [
            max(1, (int) round($width * $scale)),
            max(1, (int) round($height * $scale)),
        ];
    }

    /**
     * @param array{0: int, 1: int, 2: int, 3: string, mime?: string, channels?: int, bits?: int} $info
     */
    private function detectMime(array $info): string
    {
        $mime = match ($info[2]) {
            IMAGETYPE_JPEG => 'image/jpeg',
            IMAGETYPE_PNG => 'image/png',
            IMAGETYPE_WEBP => 'image/webp',
            default => strtolower((string) ($info['mime'] ?? '')),
        };

        return $mime === 'image/jpg' ? 'image/jpeg' : $mime;
    }

    private function assertGdAvailable(): void
    {
        if (!self::isAvailable()) {
            throw new FlatFileException(Lang::get('optimize_gd_required', [], 'media'));
        }
    }

    private function assertFormatSupported(string $mimeType): void
    {
        $capabilities = self::capabilities();

        $supported = match ($mimeType) {
            'image/jpeg', 'image/jpg' => $capabilities['jpeg'],
            'image/png' => $capabilities['png'],
            'image/webp' => $capabilities['webp'],
            default => false,
        };

        if ($supported) {
            return;
        }

        throw new FlatFileException(match ($mimeType) {
            'image/jpeg', 'image/jpg' => Lang::get('optimize_gd_jpeg', [], 'media'),
            'image/png' => Lang::get('optimize_gd_png', [], 'media'),
            'image/webp' => Lang::get('optimize_gd_webp', [], 'media'),
            default => Lang::get('optimize_unsupported_type', [], 'media'),
        });
    }

    /**
     * @param \GdImage $source
     */
    private function renderImage(
        $source,
        int $srcW,
        int $srcH,
        int $targetW,
        int $targetH,
        string $mimeType,
    ): string {
        if ($srcW < 1 || $srcH < 1 || $targetW < 1 || $targetH < 1) {
            throw new FlatFileException(Lang::get('optimize_invalid_dimensions', [], 'media'));
        }

        $canvas = imagecreatetruecolor($targetW, $targetH);
        if ($canvas === false) {
            throw new FlatFileException(Lang::get('optimize_encode_failed', [], 'media'));
        }

        if ($mimeType === 'image/png' || $mimeType === 'image/webp') {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            if ($transparent === false) {
                imagedestroy($canvas);
                throw new FlatFileException(Lang::get('optimize_encode_failed', [], 'media'));
            }
            imagefilledrectangle($canvas, 0, 0, $targetW, $targetH, $transparent);
        } else {
            $background = imagecolorallocate($canvas, 255, 255, 255);
            if ($background === false) {
                imagedestroy($canvas);
                throw new FlatFileException(Lang::get('optimize_encode_failed', [], 'media'));
            }
            imagefilledrectangle($canvas, 0, 0, $targetW, $targetH, $background);
        }

        if ($targetW === $srcW && $targetH === $srcH) {
            imagecopy($canvas, $source, 0, 0, 0, 0, $srcW, $srcH);
        } else {
            imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetW, $targetH, $srcW, $srcH);
        }

        ob_start();
        $saved = match ($mimeType) {
            'image/jpeg', 'image/jpg' => imagejpeg($canvas, null, self::JPEG_QUALITY),
            'image/png' => imagepng($canvas, null, self::PNG_COMPRESSION),
            'image/webp' => imagewebp($canvas, null, self::WEBP_QUALITY),
            default => false,
        };

        imagedestroy($canvas);

        $encoded = ob_get_clean();
        if ($saved === false) {
            return '';
        }

        return $encoded;
    }

    private function normalizeMime(string $mimeType): string
    {
        return $mimeType === 'image/jpg' ? 'image/jpeg' : $mimeType;
    }
}
