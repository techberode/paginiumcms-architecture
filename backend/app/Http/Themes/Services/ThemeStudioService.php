<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Themes\Services;

use FilesystemIterator;
use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;
use PaginiumCMS\Http\Themes\Exceptions\ThemeStudioException;
use PaginiumCMS\Support\LogSanitizer;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Allow-listed theme package file access (It.88a) plus persist helpers (It.88g/e).
 *
 * Path checks are shared with validate (88b). Text writes never leave the theme id directory.
 */
final class ThemeStudioService
{
    public const MAX_FILE_BYTES = 524288;

    public const MAX_THUMBNAIL_BYTES = 524288;

    public const CORE_THEME_ID = 'paginium-core';

    /** @var list<string> */
    private const ALLOWED_EXTENSIONS = ['html', 'css', 'js', 'json', 'md'];

    public function __construct(
        private string $themesRoot,
        private ?LoggerInterface $logger = null,
    ) {
        $this->themesRoot = rtrim($themesRoot, '/\\');
    }

    /**
     * @return list<array{relativePath: string, language: string, tab: string, size: int, tooLarge: bool}>
     */
    public function listFiles(string $themeId): array
    {
        $themeReal = $this->resolveThemeDirectory($themeId);
        $items = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($themeReal, FilesystemIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $absolute = $file->getPathname();
            $relative = $this->relativePathFromAbsolute($themeReal, $absolute);
            if ($relative === null) {
                continue;
            }

            $extension = $this->extensionOf($relative);
            if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                continue;
            }

            $size = $file->getSize();
            if ($size === false) {
                continue;
            }

            $items[] = [
                'relativePath' => $relative,
                'language' => $this->languageForExtension($extension),
                'tab' => $this->tabForExtension($extension),
                'size' => $size,
                'tooLarge' => $size > self::MAX_FILE_BYTES,
            ];
        }

        usort(
            $items,
            static fn (array $a, array $b): int => strcmp($a['relativePath'], $b['relativePath'])
        );

        return $items;
    }

    /**
     * @return array{relativePath: string, content: string, language: string, tab: string, size: int}
     */
    public function readFile(string $themeId, string $relativePath): array
    {
        $themeReal = $this->resolveThemeDirectory($themeId);
        $normalized = $this->normalizeRelativePath($relativePath);
        $extension = $this->extensionOf($normalized);
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $this->logRejected($themeId, $normalized, 'disallowed extension');
            throw new ThemeStudioException('Theme file type is not allowed.', 400);
        }

        $candidate = $themeReal . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);
        $realFile = realpath($candidate);
        if ($realFile === false || !is_file($realFile)) {
            throw new ThemeStudioException('Theme file not found.', 404);
        }

        if (!$this->isPathInside($themeReal, $realFile)) {
            $this->logRejected($themeId, $normalized, 'path escape');
            throw new ThemeStudioException('Invalid theme file path.', 400);
        }

        $size = filesize($realFile);
        if ($size === false) {
            throw new ThemeStudioException('Unable to read theme file.', 500);
        }

        if ($size > self::MAX_FILE_BYTES) {
            throw new ThemeStudioException('Theme file is too large to open in the studio.', 413);
        }

        $content = file_get_contents($realFile);
        if ($content === false) {
            throw new ThemeStudioException('Unable to read theme file.', 500);
        }

        return [
            'relativePath' => $normalized,
            'content' => $content,
            'language' => $this->languageForExtension($extension),
            'tab' => $this->tabForExtension($extension),
            'size' => $size,
        ];
    }

    /**
     * Normalize an in-memory studio path without touching disk (It.88b).
     */
    public function assertBufferPath(string $relativePath): string
    {
        $path = $this->normalizeRelativePath($relativePath);
        $extension = $this->extensionOf($path);
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new ThemeStudioException('Theme file type is not allowed.', 400);
        }

        return $path;
    }

    public function hasPreviewPng(string $themeId): bool
    {
        return $this->previewPngPath($themeId) !== null;
    }

    public function readPreviewPng(string $themeId): string
    {
        $path = $this->previewPngPath($themeId);
        if ($path === null) {
            throw new ThemeStudioException('Theme thumbnail not found.', 404);
        }

        $bytes = file_get_contents($path);
        if ($bytes === false) {
            throw new ThemeStudioException('Unable to read theme thumbnail.', 500);
        }

        return $bytes;
    }

    public function writePreviewPng(string $themeId, string $bytes): void
    {
        $this->assertSafeThemeId($themeId);
        $this->assertPngThumbnail($bytes);
        $themeReal = $this->resolveThemeDirectory($themeId);
        $target = $themeReal . DIRECTORY_SEPARATOR . 'preview.png';
        if (@file_put_contents($target, $bytes, LOCK_EX) === false) {
            throw new ThemeStudioException('Unable to write theme thumbnail.', 500);
        }

        $real = realpath($target);
        if ($real === false || !$this->isPathInside($themeReal, $real)) {
            @unlink($target);
            $this->logRejected($themeId, 'preview.png', 'path escape');
            throw new ThemeStudioException('Invalid theme file path.', 400);
        }
    }

    /**
     * @param array<string, string> $files
     * @return list<string>
     */
    public function writeTextFiles(string $themeId, array $files): array
    {
        $this->assertSafeThemeId($themeId);
        $themeReal = $this->ensureThemeDirectory($themeId);
        $written = [];

        foreach ($files as $relativePath => $content) {
            $relative = $this->assertBufferPath($relativePath);
            if (strlen($content) > self::MAX_FILE_BYTES) {
                throw new ThemeStudioException('Theme file is too large to open in the studio.', 413);
            }

            $target = $themeReal . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            $parent = dirname($target);
            $this->ensureDirectory($parent);
            $parentReal = realpath($parent);
            if ($parentReal === false || !$this->isPathInside($themeReal, $parentReal)) {
                $this->logRejected($themeId, $relative, 'path escape');
                throw new ThemeStudioException('Invalid theme file path.', 400);
            }

            if (@file_put_contents($target, $content, LOCK_EX) === false) {
                throw new ThemeStudioException('Unable to write theme file.', 500);
            }

            $fileReal = realpath($target);
            if ($fileReal === false || !$this->isPathInside($themeReal, $fileReal)) {
                @unlink($target);
                $this->logRejected($themeId, $relative, 'path escape');
                throw new ThemeStudioException('Invalid theme file path.', 400);
            }

            $written[] = $relative;
        }

        sort($written, SORT_STRING);

        return $written;
    }

    public function ensureThemeDirectory(string $themeId): string
    {
        $this->assertSafeThemeId($themeId);
        $rootReal = realpath($this->themesRoot);
        if ($rootReal === false || !is_dir($rootReal)) {
            throw new ThemeStudioException('Theme storage is not available.', 500);
        }

        $candidate = $this->themesRoot . DIRECTORY_SEPARATOR . $themeId;
        if (!is_dir($candidate) && !@mkdir($candidate, 0775, true) && !is_dir($candidate)) {
            throw new ThemeStudioException('Unable to create theme directory.', 500);
        }

        $real = realpath($candidate);
        if ($real === false || !$this->isPathInside($rootReal, $real)) {
            $this->logRejected($themeId, '', 'theme directory escape');
            throw new ThemeStudioException('Invalid theme id.', 400);
        }

        return $real;
    }

    public static function isValidThemeId(string $id): bool
    {
        return preg_match('/^[a-z][a-z0-9-]{0,63}$/', $id) === 1;
    }

    public function assertSafeThemeId(string $themeId): void
    {
        $id = trim($themeId);
        if ($id === '' || !self::isValidThemeId($id) || $id === 'new' || $id === self::CORE_THEME_ID) {
            throw new ThemeStudioException('Invalid theme id.', 400);
        }
    }

    private function resolveThemeDirectory(string $themeId): string
    {
        $id = trim($themeId);
        if ($id === '' || !self::isValidThemeId($id)) {
            throw new ThemeStudioException('Invalid theme id.', 400);
        }

        $candidate = $this->themesRoot . DIRECTORY_SEPARATOR . $id;
        $real = realpath($candidate);
        if ($real === false || !is_dir($real)) {
            throw new ThemeStudioException('Theme not found.', 404);
        }

        $rootReal = realpath($this->themesRoot);
        if ($rootReal === false || !$this->isPathInside($rootReal, $real)) {
            $this->logRejected($id, '', 'theme directory escape');
            throw new ThemeStudioException('Invalid theme id.', 400);
        }

        return $real;
    }

    private function previewPngPath(string $themeId): ?string
    {
        try {
            $themeReal = $this->resolveThemeDirectory($themeId);
        } catch (ThemeStudioException) {
            return null;
        }

        $candidate = $themeReal . DIRECTORY_SEPARATOR . 'preview.png';
        $real = realpath($candidate);
        if ($real === false || !is_file($real) || !$this->isPathInside($themeReal, $real)) {
            return null;
        }

        return $real;
    }

    private function assertPngThumbnail(string $bytes): void
    {
        if ($bytes === '' || strlen($bytes) > self::MAX_THUMBNAIL_BYTES) {
            throw new ThemeStudioException('Theme thumbnail is too large.', 413);
        }

        if (!str_starts_with($bytes, "\x89PNG\r\n\x1a\n")) {
            throw new ThemeStudioException('Theme thumbnail must be a PNG image.', 400);
        }

        $info = @getimagesizefromstring($bytes);
        if ($info === false || $info[2] !== IMAGETYPE_PNG) {
            throw new ThemeStudioException('Theme thumbnail must be a PNG image.', 400);
        }

        $width = $info[0];
        $height = $info[1];
        if ($width < 1 || $height < 1 || $width > 2048 || $height > 2048) {
            throw new ThemeStudioException('Theme thumbnail dimensions are not allowed.', 400);
        }
    }

    private function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        if (!@mkdir($path, 0775, true) && !is_dir($path)) {
            throw new ThemeStudioException('Unable to create theme directory.', 500);
        }
    }

    private function normalizeRelativePath(string $relativePath): string
    {
        $path = str_replace('\\', '/', $relativePath);
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, "\0") || str_contains($path, '..')) {
            throw new ThemeStudioException('Invalid theme file path.', 400);
        }

        if (str_starts_with($path, '/')) {
            throw new ThemeStudioException('Invalid theme file path.', 400);
        }

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw new ThemeStudioException('Invalid theme file path.', 400);
            }
        }

        return $path;
    }

    private function relativePathFromAbsolute(string $themeReal, string $absolute): ?string
    {
        $realFile = realpath($absolute);
        if ($realFile === false || !$this->isPathInside($themeReal, $realFile)) {
            return null;
        }

        $prefix = $themeReal . DIRECTORY_SEPARATOR;
        if (!str_starts_with($realFile, $prefix)) {
            return null;
        }

        $relative = str_replace('\\', '/', substr($realFile, strlen($prefix)));
        if ($relative === '' || str_contains($relative, '..')) {
            return null;
        }

        $base = basename($relative);
        if (str_starts_with($base, '.')) {
            return null;
        }

        return $relative;
    }

    private function isPathInside(string $baseReal, string $pathReal): bool
    {
        $base = rtrim($baseReal, DIRECTORY_SEPARATOR);
        if ($pathReal === $base) {
            return true;
        }

        return str_starts_with($pathReal, $base . DIRECTORY_SEPARATOR);
    }

    private function extensionOf(string $relativePath): string
    {
        $dot = strrpos($relativePath, '.');
        if ($dot === false) {
            return '';
        }

        return strtolower(substr($relativePath, $dot + 1));
    }

    private function languageForExtension(string $extension): string
    {
        return match ($extension) {
            'html' => 'html',
            'css' => 'css',
            'js' => 'javascript',
            'json' => 'json',
            'md' => 'markdown',
            default => 'plaintext',
        };
    }

    private function tabForExtension(string $extension): string
    {
        return match ($extension) {
            'html' => 'html',
            'css' => 'css',
            'js' => 'js',
            'json' => 'manifest',
            default => 'other',
        };
    }

    private function logRejected(string $themeId, string $path, string $reason): void
    {
        $this->logger?->warning('Theme studio rejected path', LogSanitizer::context([
            'themeId' => $themeId,
            'path' => $path,
            'reason' => $reason,
        ]));
    }
}
