<?php

declare(strict_types=1);

namespace PaginiumCMS\Support;

use JsonException;

final class JsonHelper
{
    /**
     * @return array<int|string, mixed>
     */
    public static function decode(string $json, int $depth = 512, int $flags = 0): array
    {
        $safeDepth = max(1, min(512, $depth));
        $decoded = json_decode($json, true, $safeDepth, $flags | JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            throw new JsonException('Expected JSON object or array.');
        }

        return $decoded;
    }

    public static function encode(mixed $data, int $flags = 0): string
    {
        return json_encode($data, $flags | JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

final class FileHelper
{
    public static function read(string $path): string
    {
        if (!is_file($path) || !is_readable($path)) {
            return '';
        }

        $content = @file_get_contents($path);

        return $content === false ? '' : $content;
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function readJson(string $path): array
    {
        return JsonHelper::decode(self::read($path));
    }
}
