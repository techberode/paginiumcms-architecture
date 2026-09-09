<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Themes\Services;

use RuntimeException;

/**
 * SRI (sha384) for declared theme assets/*.js (It.87l).
 */
final class ThemeScriptIntegrityService
{
    public const PATH_PATTERN = '#^assets/[a-zA-Z0-9/_-]+\.js$#';

    public function __construct(
        private string $themesRoot,
    ) {
        $this->themesRoot = rtrim($themesRoot, '/');
    }

    /**
     * @param array<string, mixed> $manifest
     *
     * @return list<string>
     */
    public function declaredScriptPaths(array $manifest): array
    {
        $scripts = $manifest['assets']['scripts'] ?? [];
        if (!is_array($scripts)) {
            return [];
        }

        $paths = [];
        foreach ($scripts as $script) {
            if (!is_array($script)) {
                continue;
            }
            $path = trim((string) ($script['path'] ?? ''));
            if ($path !== '' && preg_match(self::PATH_PATTERN, $path) === 1) {
                $paths[] = $path;
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * @param array<string, mixed> $manifest
     */
    public function assertJsAllowList(string $themeRoot, array $manifest): void
    {
        $themeRoot = rtrim($themeRoot, '/');
        $allowed = $this->declaredScriptPaths($manifest);
        $found = $this->collectJsFiles($themeRoot);

        foreach ($found as $relative) {
            if (!in_array($relative, $allowed, true)) {
                throw new RuntimeException('Undeclared theme JavaScript is not allowed: ' . $relative);
            }
        }

        foreach ($allowed as $path) {
            if (!is_file($themeRoot . '/' . $path)) {
                throw new RuntimeException('Declared theme script is missing: ' . $path);
            }
        }
    }

    /**
     * @param array<string, mixed> $manifest
     *
     * @return array<string, mixed>
     */
    public function sealManifest(string $themeRoot, array $manifest): array
    {
        $themeRoot = rtrim($themeRoot, '/');
        $assets = is_array($manifest['assets'] ?? null) ? $manifest['assets'] : [];
        $scripts = is_array($assets['scripts'] ?? null) ? $assets['scripts'] : [];
        $sealed = [];
        foreach ($scripts as $script) {
            if (!is_array($script)) {
                continue;
            }
            $path = trim((string) ($script['path'] ?? ''));
            if ($path === '' || preg_match(self::PATH_PATTERN, $path) !== 1) {
                continue;
            }
            $absolute = $themeRoot . '/' . $path;
            $content = @file_get_contents($absolute);
            if ($content === false) {
                throw new RuntimeException('Unable to read theme script for integrity: ' . $path);
            }
            $script['integrity'] = $this->integrityFor($content);
            if (!isset($script['load']) || !is_string($script['load']) || $script['load'] === '') {
                $script['load'] = 'defer';
            }
            $sealed[] = $script;
        }
        $assets['scripts'] = $sealed;
        $manifest['assets'] = $assets;

        $encoded = json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($encoded === false) {
            throw new RuntimeException('Unable to encode sealed theme.json.');
        }
        if (@file_put_contents($themeRoot . '/theme.json', $encoded . "\n") === false) {
            throw new RuntimeException('Unable to write sealed theme.json.');
        }

        return $manifest;
    }

    public function assertActivation(string $themeId): void
    {
        $root = $this->themesRoot . '/' . $themeId;
        $manifest = $this->readManifest($root);
        $this->assertJsAllowList($root, $manifest);

        $scripts = $manifest['assets']['scripts'] ?? [];
        if (!is_array($scripts)) {
            return;
        }

        foreach ($scripts as $script) {
            if (!is_array($script)) {
                continue;
            }
            $path = trim((string) ($script['path'] ?? ''));
            if ($path === '' || preg_match(self::PATH_PATTERN, $path) !== 1) {
                continue;
            }
            $stored = (string) ($script['integrity'] ?? '');
            $content = @file_get_contents($root . '/' . $path);
            if ($content === false) {
                throw new RuntimeException('Theme script missing at activate: ' . $path);
            }
            $actual = $this->integrityFor($content);
            if ($stored === '' || !hash_equals($stored, $actual)) {
                throw new RuntimeException('Theme script integrity mismatch: ' . $path);
            }
        }
    }

    /**
     * @return list<string>
     */
    public function cspHashTokens(string $themeId): array
    {
        $tokens = [];
        foreach ($this->scriptRecords($themeId) as $script) {
            $integrity = (string) ($script['integrity'] ?? '');
            if (str_starts_with($integrity, 'sha384-')) {
                $tokens[] = "'" . $integrity . "'";
            }
        }

        return $tokens;
    }

    /**
     * @return list<array{src: string, integrity: string, load: string}>
     */
    public function publicScripts(string $themeId): array
    {
        if ($themeId === ThemeRuntimeService::CORE_THEME_ID || $themeId === '') {
            return [];
        }

        $out = [];
        foreach ($this->scriptRecords($themeId) as $script) {
            $path = trim((string) ($script['path'] ?? ''));
            $integrity = trim((string) ($script['integrity'] ?? ''));
            if ($path === '' || $integrity === '') {
                continue;
            }
            $load = (string) ($script['load'] ?? 'defer');
            if (!in_array($load, ['defer', 'async', 'blocking'], true)) {
                $load = 'defer';
            }
            $out[] = [
                'src' => '/theme-assets/' . $themeId . '/' . $path,
                'integrity' => $integrity,
                'load' => $load,
            ];
        }

        return $out;
    }

    public function integrityFor(string $content): string
    {
        return 'sha384-' . base64_encode(hash('sha384', $content, true));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function scriptRecords(string $themeId): array
    {
        $manifest = $this->readManifest($this->themesRoot . '/' . $themeId);
        $scripts = $manifest['assets']['scripts'] ?? [];
        if (!is_array($scripts)) {
            return [];
        }

        $records = [];
        foreach ($scripts as $script) {
            if (is_array($script)) {
                $records[] = $script;
            }
        }

        return $records;
    }

    /**
     * @return array<string, mixed>
     */
    private function readManifest(string $themeRoot): array
    {
        $path = rtrim($themeRoot, '/') . '/theme.json';
        if (!is_file($path)) {
            return [];
        }
        $raw = @file_get_contents($path);
        if ($raw === false || trim($raw) === '') {
            return [];
        }
        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

            return $decoded;
        } catch (\JsonException) {
            return [];
        }
    }

    /**
     * @return list<string>
     */
    private function collectJsFiles(string $themeRoot): array
    {
        if (!is_dir($themeRoot)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($themeRoot, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        $rootLen = strlen($themeRoot);
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), $rootLen)), '/');
            if ($relative === '' || str_starts_with($relative, 'frontend/')) {
                continue;
            }
            if (str_ends_with(strtolower($relative), '.js')) {
                $files[] = $relative;
            }
        }
        sort($files);

        return $files;
    }
}
