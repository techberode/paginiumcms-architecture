<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Extensions\Services;

use PaginiumCMS\Core\Hook\HookCatalog;
use PaginiumCMS\Core\Hook\HookManager;
use PaginiumCMS\Core\Hook\Services\HookEmitter;
use PaginiumCMS\Http\Extensions\Contracts\PluginManagerInterface;
use PaginiumCMS\Http\Extensions\Models\PluginRecord;
use PaginiumCMS\Support\JsonHelper;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * Discovers extensions under Http/Extensions/{id}/plugin.json and syncs with data/plugins.json.
 */
final class PluginManager implements PluginManagerInterface
{
    /** @var list<string> */
    private array $bootedIds = [];

    public function __construct(
        private PluginRegistry $registry,
        private PluginImporter $importer,
        private HookManager $hookManager,
        private HookEmitter $hookEmitter,
        private ExtensionManifestValidator $manifestValidator,
        private string $extensionsRoot,
        private string $extensionRoutesRoot,
        private string $frontendExtensionsRoot,
    ) {
        $this->extensionsRoot = rtrim($extensionsRoot, '/');
        $this->extensionRoutesRoot = rtrim($extensionRoutesRoot, '/');
        $this->frontendExtensionsRoot = rtrim($frontendExtensionsRoot, '/');
    }

    public function bootEnabledExtensions(): void
    {
        foreach ($this->registry->all() as $id => $record) {
            if (!$record->enabled || in_array($id, $this->bootedIds, true)) {
                continue;
            }

            $manifest = $this->loadManifest($id);
            if ($manifest === null) {
                continue;
            }

            $this->loadPluginClasses($id);
            $this->registerHooks($id, $manifest);
            $this->hookEmitter->emit(HookCatalog::EXTENSION_BOOT, [
                'id' => $id,
                'manifest' => $manifest,
            ]);
            $this->bootedIds[] = $id;
        }
    }

    /**
     * @return list<string>
     */
    public function getEnabledIds(): array
    {
        $ids = [];
        foreach ($this->registry->all() as $id => $record) {
            if ($record->enabled) {
                $ids[] = $id;
            }
        }

        sort($ids);

        return $ids;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        $registry = $this->registry->all();
        $discovered = $this->discoverManifests();
        $items = [];

        foreach ($discovered as $id => $manifest) {
            $record = $registry[$id] ?? null;
            $items[] = $this->mergeEntry($id, $manifest, $record);
        }

        foreach ($registry as $id => $record) {
            if (isset($discovered[$id])) {
                continue;
            }

            $items[] = [
                'id' => $id,
                'name' => $id,
                'version' => '',
                'description' => '',
                'enabled' => $record->enabled,
                'installedAt' => $record->installedAt,
                'present' => false,
            ];
        }

        usort($items, static fn (array $a, array $b): int => strcmp((string) $a['id'], (string) $b['id']));

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    public function import(string $zipPath): array
    {
        return $this->importer->importZip($zipPath);
    }

    public function enable(string $id): void
    {
        $id = $this->normalizeId($id);
        $manifest = $this->loadManifest($id);
        if ($manifest === null) {
            throw new RuntimeException('Extension not found: ' . $id);
        }

        $this->manifestValidator->validate($manifest, $id);

        $existing = $this->registry->get($id);
        $installedAt = $existing?->installedAt !== '' && $existing !== null
            ? $existing->installedAt
            : gmdate('c');

        $this->registry->upsert(new PluginRecord($id, true, $installedAt));
        $this->loadPluginClasses($id);
        $this->registerHooks($id, $manifest);
        $this->hookEmitter->emit(HookCatalog::EXTENSION_ENABLED, [
            'id' => $id,
            'manifest' => $manifest,
        ]);
        if (!in_array($id, $this->bootedIds, true)) {
            $this->hookEmitter->emit(HookCatalog::EXTENSION_BOOT, [
                'id' => $id,
                'manifest' => $manifest,
            ]);
            $this->bootedIds[] = $id;
        }
    }

    public function disable(string $id): void
    {
        $id = $this->normalizeId($id);
        $existing = $this->registry->get($id);
        if ($existing === null) {
            throw new RuntimeException('Extension is not registered: ' . $id);
        }

        $this->hookEmitter->emit(HookCatalog::EXTENSION_DISABLED, ['id' => $id]);
        $this->unregisterHooks($id);
        $this->registry->upsert(new PluginRecord($id, false, $existing->installedAt));
        $this->bootedIds = array_values(array_filter(
            $this->bootedIds,
            static fn (string $bootedId): bool => $bootedId !== $id
        ));
    }

    public function uninstall(string $id): void
    {
        $id = $this->normalizeId($id);
        $this->unregisterHooks($id);
        $this->registry->remove($id);
        $this->removeInstalledFiles($id);
        $this->bootedIds = array_values(array_filter(
            $this->bootedIds,
            static fn (string $bootedId): bool => $bootedId !== $id
        ));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function discoverManifests(): array
    {
        if (!is_dir($this->extensionsRoot)) {
            return [];
        }

        $manifests = [];
        $entries = scandir($this->extensionsRoot);
        if ($entries === false) {
            return [];
        }

        foreach ($entries as $entry) {
            if ($this->isInternalDirectory($entry)) {
                continue;
            }

            $dir = $this->extensionsRoot . '/' . $entry;
            if (!is_dir($dir)) {
                continue;
            }

            $manifestPath = $dir . '/plugin.json';
            if (!is_file($manifestPath)) {
                continue;
            }

            $manifest = $this->readManifestFile($manifestPath);
            if ($manifest === null) {
                continue;
            }

            $pluginId = (string) ($manifest['id'] ?? $entry);
            $manifests[$this->normalizeId($pluginId)] = $manifest;
        }

        return $manifests;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadManifest(string $id): ?array
    {
        $manifestPath = $this->extensionsRoot . '/' . $id . '/plugin.json';
        if (!is_file($manifestPath)) {
            return null;
        }

        return $this->readManifestFile($manifestPath);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readManifestFile(string $path): ?array
    {
        $raw = @file_get_contents($path);
        if ($raw === false || trim($raw) === '') {
            return null;
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = JsonHelper::decode($raw);

            return $decoded;
        } catch (\JsonException) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $manifest
     * @return array<string, mixed>
     */
    private function mergeEntry(string $id, array $manifest, ?PluginRecord $record): array
    {
        return [
            'id' => $id,
            'name' => (string) ($manifest['name'] ?? $id),
            'version' => (string) ($manifest['version'] ?? ''),
            'description' => (string) ($manifest['description'] ?? ''),
            'author' => (string) ($manifest['author'] ?? ''),
            'enabled' => $record !== null ? $record->enabled : false,
            'installedAt' => $record !== null ? $record->installedAt : '',
            'present' => true,
            'hasRoutes' => is_file($this->extensionRoutesRoot . '/' . $id . '.php')
                || (bool) ($manifest['routes'] ?? false),
            'hasFrontend' => is_dir($this->frontendExtensionsRoot . '/' . $id)
                || (bool) ($manifest['frontend'] ?? false),
        ];
    }

    private function loadPluginClasses(string $id): void
    {
        $root = $this->extensionsRoot . '/' . $id;
        if (!is_dir($root)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            if (strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            if ($file->getFilename() === 'plugin.json') {
                continue;
            }

            require_once $file->getPathname();
        }
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function registerHooks(string $id, array $manifest): void
    {
        $hooks = $manifest['hooks'] ?? [];
        if (!is_array($hooks)) {
            return;
        }

        foreach ($hooks as $hookName => $callable) {
            if (!is_string($hookName) || !HookCatalog::isRegistered($hookName)) {
                continue;
            }

            if (!is_string($callable) || $callable === '') {
                continue;
            }

            if (!is_callable($callable)) {
                continue;
            }

            $this->hookManager->add($hookName, $callable);
        }
    }

    private function unregisterHooks(string $id): void
    {
        $manifest = $this->loadManifest($id);
        if ($manifest === null) {
            return;
        }

        $hooks = $manifest['hooks'] ?? [];
        if (!is_array($hooks)) {
            return;
        }

        foreach (array_keys($hooks) as $hookName) {
            if (is_string($hookName)) {
                $this->hookManager->remove($hookName);
            }
        }
    }

    private function removeInstalledFiles(string $id): void
    {
        $this->removeDirectory($this->extensionsRoot . '/' . $id);

        $routeFile = $this->extensionRoutesRoot . '/' . $id . '.php';
        if (is_file($routeFile)) {
            @unlink($routeFile);
        }

        $this->removeDirectory($this->frontendExtensionsRoot . '/' . $id);
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        foreach (scandir($path) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $entry = $path . '/' . $item;
            if (is_dir($entry)) {
                $this->removeDirectory($entry);
            } else {
                @unlink($entry);
            }
        }

        @rmdir($path);
    }

    private function isInternalDirectory(string $entry): bool
    {
        return in_array($entry, ['.', '..', 'Services', 'Models', 'Contracts'], true);
    }

    private function normalizeId(string $id): string
    {
        return trim($id);
    }
}
