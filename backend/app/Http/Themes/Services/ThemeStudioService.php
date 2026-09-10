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
 * Read-only allow-listed access to theme package files on disk (It.88a).
 *
     * Persist / preview ship in later 88 slices. Path checks are also used by validate (88b).
     */
    final class ThemeStudioService
{
    public const MAX_FILE_BYTES = 524288;

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

    public static function isValidThemeId(string $id): bool
    {
        return preg_match('/^[a-z][a-z0-9-]{0,63}$/', $id) === 1;
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
