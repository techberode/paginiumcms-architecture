<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Themes\Services;

use PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException;
use PaginiumCMS\Core\CodePolicy\Services\UntrustedPolicyScanner;
use PaginiumCMS\Http\Themes\Models\ThemeRecord;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;
use ZipArchive;

/**
 * ZIP import pipeline for theme packages: extract → validate manifest + policy → install (It.67b).
 */
final class ThemeImporter
{
    public function __construct(
        private ThemeRegistry $registry,
        private UntrustedPolicyScanner $policyScanner,
        private ThemeManifestValidator $manifestValidator,
        private string $themesRoot,
        private string $frontendThemesRoot,
        private string $projectRoot,
    ) {
        $this->themesRoot = rtrim($themesRoot, '/');
        $this->frontendThemesRoot = rtrim($frontendThemesRoot, '/');
        $this->projectRoot = rtrim($projectRoot, '/');
    }

    /**
     * @return array<string, mixed>
     */
    public function importZip(string $zipPath): array
    {
        if (!is_file($zipPath)) {
            throw new RuntimeException('ZIP archive not found.');
        }

        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('ZipArchive PHP extension is required.');
        }

        $tempDir = sys_get_temp_dir() . '/pag_theme_import_' . uniqid('', true);
        if (!mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
            throw new RuntimeException('Unable to create temporary import directory.');
        }

        try {
            $this->extractZip($zipPath, $tempDir);
            [$themeRoot, $manifest] = $this->resolveThemeRoot($tempDir);
            $id = $this->manifestValidator->validate($manifest, basename($themeRoot));

            $targetDir = $this->themesRoot . '/' . $id;
            if (is_dir($targetDir)) {
                throw new RuntimeException('Theme already installed: ' . $id);
            }

            $policyPrefix = 'themes/' . $id;
            $errors = $this->policyScanner->scanDirectory($themeRoot, $policyPrefix);
            if ($errors !== []) {
                throw new CodePolicyViolationException($this->mapScanErrors($errors));
            }

            $this->installThemeFiles($themeRoot, $id);

            $record = new ThemeRecord($id, false, gmdate('c'));
            $this->registry->upsert($record);

            return [
                'id' => $id,
                'name' => (string) ($manifest['name'] ?? $id),
                'version' => (string) ($manifest['version'] ?? ''),
                'enabled' => false,
                'installedAt' => $record->installedAt,
            ];
        } finally {
            $this->removeDir($tempDir);
        }
    }

    private function extractZip(string $zipPath, string $destination): void
    {
        $zip = new ZipArchive();
        $opened = $zip->open($zipPath);
        if ($opened !== true) {
            throw new RuntimeException('Unable to open ZIP archive.');
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = (string) $zip->getNameIndex($i);
            if (!$this->isSafeZipEntry($entryName)) {
                $zip->close();
                throw new RuntimeException('Unsafe ZIP entry rejected: ' . $entryName);
            }
        }

        if (!$zip->extractTo($destination)) {
            $zip->close();
            throw new RuntimeException('Unable to extract ZIP archive.');
        }

        $zip->close();
    }

    private function isSafeZipEntry(string $entryName): bool
    {
        if ($entryName === '' || str_contains($entryName, "\0")) {
            return false;
        }

        $normalized = str_replace('\\', '/', $entryName);

        if (str_starts_with($normalized, '/') || preg_match('#^[a-zA-Z]:/#', $normalized) === 1) {
            return false;
        }

        foreach (explode('/', $normalized) as $segment) {
            if ($segment === '..') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function resolveThemeRoot(string $extractDir): array
    {
        $manifestAtRoot = $extractDir . '/theme.json';
        if (is_file($manifestAtRoot)) {
            return [$extractDir, $this->readManifest($manifestAtRoot)];
        }

        $entries = array_values(array_filter(scandir($extractDir) ?: [], static fn (string $entry): bool => !in_array($entry, ['.', '..'], true)));
        if (count($entries) === 1) {
            $candidate = $extractDir . '/' . $entries[0];
            if (is_dir($candidate) && is_file($candidate . '/theme.json')) {
                return [$candidate, $this->readManifest($candidate . '/theme.json')];
            }
        }

        throw new RuntimeException('theme.json not found in ZIP root or single theme folder.');
    }

    /**
     * @return array<string, mixed>
     */
    private function readManifest(string $path): array
    {
        $raw = @file_get_contents($path);
        if ($raw === false || trim($raw) === '') {
            throw new RuntimeException('theme.json is empty or unreadable.');
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = JsonHelper::decode($raw);
        } catch (\JsonException $exception) {
            throw new RuntimeException('theme.json is invalid JSON: ' . $exception->getMessage());
        }

        return $decoded;
    }

    private function installThemeFiles(string $themeRoot, string $id): void
    {
        $targetDir = $this->themesRoot . '/' . $id;
        $this->copyDirectory($themeRoot, $targetDir, ['frontend']);

        $frontendSource = $themeRoot . '/frontend';
        if (is_dir($frontendSource)) {
            $this->copyDirectory($frontendSource, $this->frontendThemesRoot . '/' . $id);
        }
    }

    /**
     * @param list<string> $excludeTopLevel
     */
    private function copyDirectory(string $source, string $destination, array $excludeTopLevel = []): void
    {
        $this->ensureDirectory($destination);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relative = ltrim(str_replace('\\', '/', substr($item->getPathname(), strlen($source))), '/');
            if ($relative === '') {
                continue;
            }

            $topLevel = explode('/', $relative, 2)[0];
            if (in_array($topLevel, $excludeTopLevel, true)) {
                continue;
            }

            $target = $destination . '/' . $relative;
            if ($item->isDir()) {
                $this->ensureDirectory($target);
                continue;
            }

            $this->ensureDirectory(dirname($target));
            if (!copy($item->getPathname(), $target)) {
                throw new RuntimeException('Unable to copy theme file: ' . $relative);
            }
        }
    }

    /**
     * @param array<string, list<string>> $errors
     * @return array<string, list<string>>
     */
    private function mapScanErrors(array $errors): array
    {
        $mapped = [];
        foreach ($errors as $file => $messages) {
            $mapped['policy:' . $file] = $messages;
        }

        return $mapped;
    }

    private function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        if (!mkdir($path, 0775, true) && !is_dir($path)) {
            throw new RuntimeException('Unable to create directory: ' . $path);
        }
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
