<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Content;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;

/**
 * Normalizes profile/article avatar uploads (size + dimensions).
 */
final class AvatarImageProcessor
{
    public const MAX_BYTES = 524_288;

    public const MAX_DIMENSION = 512;

    /** Accept uploads up to 2 MB before server-side normalization. */
    public const MAX_UPLOAD_BYTES = 2_097_152;

    /** @var list<string> */
    public const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * @return array{binary: string, mimeType: string, extension: string}
     */
    public function process(string $binary, string $mimeType): array
    {
        $mimeType = strtolower(trim($mimeType));
        if ($binary === '') {
            throw new FlatFileException('Prázdny súbor avataru.');
        }

        if (strlen($binary) > self::MAX_UPLOAD_BYTES) {
            throw new FlatFileException('Avatar je príliš veľký (max. 2 MB pred optimalizáciou).');
        }

        $info = @getimagesizefromstring($binary);
        if ($info === false) {
            throw new FlatFileException('Súbor nie je platný obrázok.');
        }

        $detectedMime = $this->detectMime($info);
        if (!in_array($detectedMime, self::ALLOWED_MIMES, true)) {
            throw new FlatFileException('Avatar musí byť JPEG, PNG alebo WebP (max. 512×512 px, 512 KB).');
        }

        $width = (int) $info[0];
        $height = (int) $info[1];
        if ($width <= 0 || $height <= 0) {
            throw new FlatFileException('Neplatné rozmery avataru.');
        }

        $outputMime = $detectedMime;
        if ($width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION) {
            $binary = $this->resize($binary, $width, $height);
            $outputMime = 'image/png';
            $info = @getimagesizefromstring($binary);
            if ($info === false) {
                throw new FlatFileException('Nepodarilo sa spracovať avatar.');
            }
            $width = (int) $info[0];
            $height = (int) $info[1];
        }

        if (strlen($binary) > self::MAX_BYTES) {
            $binary = $this->recompress($binary, $outputMime, $width, $height);
            $outputMime = $this->guessMimeFromBinary($binary) ?? $outputMime;
        }

        if (strlen($binary) > self::MAX_BYTES) {
            throw new FlatFileException('Avatar je príliš veľký (max. 512 KB).');
        }

        return [
            'binary' => $binary,
            'mimeType' => $outputMime,
            'extension' => $this->extensionForMime($outputMime),
        ];
    }

    /**
     * @param array{0: int, 1: int, 2: int, 3: string, mime?: string} $info
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

    private function guessMimeFromBinary(string $binary): ?string
    {
        $info = @getimagesizefromstring($binary);
        if ($info === false) {
            return null;
        }

        return $this->detectMime($info);
    }

    private function extensionForMime(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            default => 'png',
        };
    }

    private function resize(string $binary, int $width, int $height): string
    {
        if (!function_exists('imagecreatetruecolor')) {
            throw new FlatFileException('Avatar presahuje 512×512 px a server nemá GD na zmenšenie.');
        }

        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            throw new FlatFileException('Nepodarilo sa spracovať avatar.');
        }

        $scale = min(self::MAX_DIMENSION / $width, self::MAX_DIMENSION / $height, 1.0);
        $targetW = max(1, (int) round($width * $scale));
        $targetH = max(1, (int) round($height * $scale));

        $resized = $this->render($source, $width, $height, $targetW, $targetH, 'image/png', 6);
        imagedestroy($source);

        if ($resized === '') {
            throw new FlatFileException('Nepodarilo sa exportovať avatar.');
        }

        return $resized;
    }

    private function recompress(string $binary, string $mimeType, int $width, int $height): string
    {
        if (!function_exists('imagecreatetruecolor')) {
            throw new FlatFileException('Avatar je príliš veľký a server nemá GD na kompresiu.');
        }

        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            throw new FlatFileException('Nepodarilo sa spracovať avatar.');
        }

        $qualities = $mimeType === 'image/png' ? [9, 8, 7] : [82, 72, 62, 52];
        $best = $binary;

        foreach ($qualities as $quality) {
            $encoded = $this->render($source, $width, $height, $width, $height, $mimeType, $quality);
            if ($encoded !== '' && strlen($encoded) < strlen($best)) {
                $best = $encoded;
            }
            if (strlen($best) <= self::MAX_BYTES) {
                break;
            }
        }

        imagedestroy($source);

        if ($best === '') {
            throw new FlatFileException('Nepodarilo sa skomprimovať avatar.');
        }

        return $best;
    }

    /**
     * @param \GdImage $source
     */
    private function render(
        $source,
        int $srcW,
        int $srcH,
        int $targetW,
        int $targetH,
        string $mimeType,
        int $quality,
    ): string {
        if ($srcW < 1 || $srcH < 1 || $targetW < 1 || $targetH < 1) {
            return '';
        }

        $canvas = imagecreatetruecolor($targetW, $targetH);
        if ($canvas === false) {
            return '';
        }

        if ($mimeType === 'image/png' || $mimeType === 'image/webp') {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            if ($transparent === false) {
                imagedestroy($canvas);

                return '';
            }
            imagefilledrectangle($canvas, 0, 0, $targetW, $targetH, $transparent);
        } else {
            $background = imagecolorallocate($canvas, 255, 255, 255);
            if ($background === false) {
                imagedestroy($canvas);

                return '';
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
            'image/jpeg', 'image/jpg' => imagejpeg($canvas, null, max(40, min(95, $quality))),
            'image/webp' => function_exists('imagewebp')
                ? imagewebp($canvas, null, max(40, min(95, $quality)))
                : false,
            default => imagepng($canvas, null, max(0, min(9, $quality))),
        };
        imagedestroy($canvas);

        $encoded = ob_get_clean();
        if ($saved === false) {
            return '';
        }

        return $encoded;
    }
}
