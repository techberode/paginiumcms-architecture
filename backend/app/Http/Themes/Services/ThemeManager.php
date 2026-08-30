<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Themes\Services;

use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Admin operations for installed theme packages (It.67b).
 */
final class ThemeManager
{
    public function __construct(
        private ThemeRegistry $registry,
        private ThemeImporter $importer,
        private ThemeRuntimeService $runtime,
        private ThemeCatalogSeeder $catalogSeeder,
        private string $themesRoot,
        private string $frontendThemesRoot,
    ) {
        $this->themesRoot = rtrim($themesRoot, '/');
        $this->frontendThemesRoot = rtrim($frontendThemesRoot, '/');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        $this->catalogSeeder->seedMissingBundled();

        $activeThemeId = $this->runtime->resolveActiveThemeId();
        $items = [];
        foreach ($this->registry->all() as $id => $record) {
            $manifest = $this->readManifestIfPresent($id);
            $items[] = [
                'id' => $id,
                'name' => (string) ($manifest['name'] ?? $id),
                'version' => (string) ($manifest['version'] ?? ''),
                'enabled' => $record->enabled,
                'active' => $id === $activeThemeId,
                'bundled' => $this->catalogSeeder->isBundled($id),
                'installedAt' => $record->installedAt,
                'present' => is_dir($this->themesRoot . '/' . $id),
            ];
        }

        usort($items, static fn (array $a, array $b): int => strcmp((string) $a['id'], (string) $b['id']));

        return $items;
    }

    public function getActiveThemeId(): string
    {
        return $this->runtime->resolveActiveThemeId();
    }

    public function getPreviousThemeId(): ?string
    {
        return $this->runtime->getPreviousThemeId();
    }

    /**
     * @return array<string, mixed>
     */
    public function import(string $zipPath): array
    {
        return $this->importer->importZip($zipPath);
    }

    public function uninstall(string $id): void
    {
        $id = trim($id);
        if ($this->registry->get($id) === null) {
            throw new RuntimeException('Theme not found: ' . $id);
        }

        if ($this->catalogSeeder->isBundled($id)) {
            throw new RuntimeException('Bundled themes cannot be uninstalled. Deactivate instead.');
        }

        $this->runtime->assertNotActive($id);

        $this->removeDir($this->themesRoot . '/' . $id);
        $this->removeDir($this->frontendThemesRoot . '/' . $id);
        $this->registry->remove($id);
    }

    /**
     * @return array{activeThemeId: string, previousThemeId: string|null}
     */
    public function activate(string $id): array
    {
        return $this->runtime->activate($id);
    }

    /**
     * @return array{activeThemeId: string, previousThemeId: string|null}
     */
    public function deactivate(): array
    {
        return $this->runtime->deactivate();
    }

    /**
     * @return array{activeThemeId: string, previousThemeId: string|null}
     */
    public function rollback(): array
    {
        return $this->runtime->rollback();
    }

    /**
     * @return array<string, mixed>
     */
    private function readManifestIfPresent(string $id): array
    {
        $path = $this->themesRoot . '/' . $id . '/theme.json';
        if (!is_file($path)) {
            return [];
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = JsonHelper::decode((string) file_get_contents($path));

            return $decoded;
        } catch (\JsonException) {
            return [];
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
