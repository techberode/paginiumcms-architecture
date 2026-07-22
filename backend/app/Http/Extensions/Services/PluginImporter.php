<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Extensions\Services;

use PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException;
use PaginiumCMS\Http\Extensions\Models\PluginRecord;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;
use ZipArchive;

/**
 * ZIP import pipeline: extract → validate manifest + policy → install files (It.15b).
 */
final class PluginImporter
{
    public function __construct(
        private PluginRegistry $registry,
        private PluginPolicyScanner $policyScanner,
        private string $extensionsRoot,
        private string $extensionRoutesRoot,
        private string $frontendExtensionsRoot,
        private string $projectRoot,
    ) {
        $this->extensionsRoot = rtrim($extensionsRoot, '/');
        $this->extensionRoutesRoot = rtrim($extensionRoutesRoot, '/');
        $this->frontendExtensionsRoot = rtrim($frontendExtensionsRoot, '/');
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

        $tempDir = sys_get_temp_dir() . '/pag_extension_import_' . uniqid('', true);
        if (!mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
            throw new RuntimeException('Unable to create temporary import directory.');
        }

        try {
            $this->extractZip($zipPath, $tempDir);
            [$pluginRoot, $manifest] = $this->resolvePluginRoot($tempDir);
            $id = $this->validateManifest($manifest, basename($pluginRoot));

            $targetDir = $this->extensionsRoot . '/' . $id;
            if (is_dir($targetDir)) {
                throw new RuntimeException('Extension already installed: ' . $id);
            }

            $policyPrefix = 'backend/app/Http/Extensions/' . $id;
            $errors = $this->policyScanner->scanDirectory($pluginRoot, $policyPrefix);
            if ($errors !== []) {
                throw new CodePolicyViolationException($this->mapScanErrors($errors));
            }

            $this->installPluginFiles($pluginRoot, $id, $manifest);

            $record = new PluginRecord($id, false, gmdate('c'));
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

        // Zip-Slip ochrana: pred extrakciou overíme, že žiadny entry nevystúpi
        // z cieľového adresára (žiadne '..', absolútne cesty, ani null-bajty).
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

    /**
     * Odmietne entry názvy, ktoré by mohli spôsobiť Zip-Slip.
     */
    private function isSafeZipEntry(string $entryName): bool
    {
        if ($entryName === '' || str_contains($entryName, "\0")) {
            return false;
        }

        $normalized = str_replace('\\', '/', $entryName);

        // Absolútne cesty (unix aj Windows) sú zakázané.
        if (str_starts_with($normalized, '/') || preg_match('#^[a-zA-Z]:/#', $normalized) === 1) {
            return false;
        }

        // Akýkoľvek segment '..' je zakázaný.
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
    private function resolvePluginRoot(string $extractDir): array
    {
        $manifestAtRoot = $extractDir . '/plugin.json';
        if (is_file($manifestAtRoot)) {
            return [$extractDir, $this->readManifest($manifestAtRoot)];
        }

        $entries = array_values(array_filter(scandir($extractDir) ?: [], static fn (string $entry): bool => !in_array($entry, ['.', '..'], true)));
        if (count($entries) === 1) {
            $candidate = $extractDir . '/' . $entries[0];
            if (is_dir($candidate) && is_file($candidate . '/plugin.json')) {
                return [$candidate, $this->readManifest($candidate . '/plugin.json')];
            }
        }

        throw new RuntimeException('plugin.json not found in ZIP root or single plugin folder.');
    }

    /**
     * @return array<string, mixed>
     */
    private function readManifest(string $path): array
    {
        $raw = @file_get_contents($path);
        if ($raw === false || trim($raw) === '') {
            throw new RuntimeException('plugin.json is empty or unreadable.');
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = JsonHelper::decode($raw);
        } catch (\JsonException $exception) {
            throw new RuntimeException('plugin.json is invalid JSON: ' . $exception->getMessage());
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function validateManifest(array $manifest, string $folderName): string
    {
        $id = trim((string) ($manifest['id'] ?? $folderName));
        if ($id === '') {
            throw new RuntimeException('plugin.json must define id.');
        }

        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $id)) {
            throw new RuntimeException('Extension id must be kebab-case.');
        }

        if (trim((string) ($manifest['name'] ?? '')) === '') {
            throw new RuntimeException('plugin.json must define name.');
        }

        if (trim((string) ($manifest['version'] ?? '')) === '') {
            throw new RuntimeException('plugin.json must define version.');
        }

        return $id;
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function installPluginFiles(string $pluginRoot, string $id, array $manifest): void
    {
        $targetDir = $this->extensionsRoot . '/' . $id;
        $this->copyDirectory($pluginRoot, $targetDir, ['routes.php', 'frontend']);

        $routesSource = $pluginRoot . '/routes.php';
        if (is_file($routesSource)) {
            $this->ensureDirectory($this->extensionRoutesRoot);
            if (!copy($routesSource, $this->extensionRoutesRoot . '/' . $id . '.php')) {
                throw new RuntimeException('Unable to install extension routes.');
            }
        } elseif ((bool) ($manifest['routes'] ?? false)) {
            throw new RuntimeException('Manifest declares routes=true but routes.php is missing.');
        }

        $frontendSource = $pluginRoot . '/frontend';
        if (is_dir($frontendSource)) {
            $this->copyDirectory($frontendSource, $this->frontendExtensionsRoot . '/' . $id);
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
                throw new RuntimeException('Unable to copy extension file: ' . $relative);
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
