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

    /** @var list<string> */
    public const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * @return array{binary: string, mimeType: string, extension: string}
     */
    public function process(string $binary, string $mimeType): array
    {
        $mimeType = strtolower(trim($mimeType));
        if (!in_array($mimeType, self::ALLOWED_MIMES, true)) {
            throw new FlatFileException('Avatar musí byť JPEG, PNG alebo WebP (max. 512×512 px, 512 KB).');
        }

        if ($binary === '') {
            throw new FlatFileException('Prázdny súbor avataru.');
        }

        if (strlen($binary) > self::MAX_BYTES * 4) {
            throw new FlatFileException('Avatar je príliš veľký (max. 512 KB).');
        }

        $info = @getimagesizefromstring($binary);
        if ($info === false) {
            throw new FlatFileException('Súbor nie je platný obrázok.');
        }

        [$width, $height] = [$info[0], $info[1]];
        if ($width <= 0 || $height <= 0) {
            throw new FlatFileException('Neplatné rozmery avataru.');
        }

        if ($width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION) {
            $binary = $this->resize($binary, $mimeType, $width, $height);
            $mimeType = 'image/png';
        }

        if (strlen($binary) > self::MAX_BYTES) {
            throw new FlatFileException('Avatar je príliš veľký (max. 512 KB).');
        }

        return [
            'binary' => $binary,
            'mimeType' => $mimeType,
            'extension' => $this->extensionForMime($mimeType),
        ];
    }

    private function extensionForMime(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            default => 'png',
        };
    }

    private function resize(string $binary, string $mimeType, int $width, int $height): string
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

        $canvas = imagecreatetruecolor($targetW, $targetH);
        if ($canvas === false) {
            imagedestroy($source);
            throw new FlatFileException('Nepodarilo sa vytvoriť canvas pre avatar.');
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        if ($transparent === false) {
            imagedestroy($source);
            imagedestroy($canvas);
            throw new FlatFileException('Nepodarilo sa alokovať farbu pre avatar.');
        }
        imagefilledrectangle($canvas, 0, 0, $targetW, $targetH, $transparent);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetW, $targetH, $width, $height);

        imagedestroy($source);

        ob_start();
        imagepng($canvas, null, 6);
        imagedestroy($canvas);
        $resized = ob_get_clean();

        if ($resized === '') {
            throw new FlatFileException('Nepodarilo sa exportovať avatar.');
        }

        return $resized;
    }
}
