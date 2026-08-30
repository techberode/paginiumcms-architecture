<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Themes\Services;

use PaginiumCMS\Http\Themes\Models\ThemeRecord;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Installs bundled theme packages from resources/theme-packages into the runtime tree (It.83e).
 */
final class ThemeCatalogSeeder
{
    /** @var list<string> */
    public const BUNDLED_IDS = [
        'clean-journal',
        'terminal-breach',
    ];

    public function __construct(
        private ThemeRegistry $registry,
        private string $packagesRoot,
        private string $themesRoot,
    ) {
        $this->packagesRoot = rtrim($packagesRoot, '/');
        $this->themesRoot = rtrim($themesRoot, '/');
    }

    public function isBundled(string $id): bool
    {
        return in_array($id, self::BUNDLED_IDS, true);
    }

    public function seedMissingBundled(): int
    {
        $added = 0;
        foreach (self::BUNDLED_IDS as $id) {
            if ($this->registry->get($id) !== null && $this->isInstalled($id)) {
                continue;
            }

            $this->installBundled($id);
            $added++;
        }

        return $added;
    }

    private function isInstalled(string $id): bool
    {
        return is_dir($this->themesRoot . '/' . $id)
            && is_file($this->themesRoot . '/' . $id . '/theme.json');
    }

    private function installBundled(string $id): void
    {
        $sourceDir = $this->packagesRoot . '/' . $id;
        if (!is_dir($sourceDir) || !is_file($sourceDir . '/theme.json')) {
            throw new RuntimeException('Bundled theme source is missing: ' . $id);
        }

        $targetDir = $this->themesRoot . '/' . $id;
        if (is_dir($targetDir)) {
            $this->removeDir($targetDir);
        }

        $this->copyTree($sourceDir, $targetDir);

        $manifest = JsonHelper::decode((string) file_get_contents($targetDir . '/theme.json'));

        $existing = $this->registry->get($id);
        $installedAt = $existing !== null ? $existing->installedAt : gmdate('c');
        $enabled = $existing !== null ? $existing->enabled : false;
        $this->registry->upsert(new ThemeRecord($id, $enabled, $installedAt));
    }

    private function copyTree(string $source, string $target): void
    {
        if (!is_dir($target) && !mkdir($target, 0755, true) && !is_dir($target)) {
            throw new RuntimeException('Unable to create theme directory: ' . $target);
        }

        foreach (scandir($source) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $from = $source . '/' . $entry;
            $to = $target . '/' . $entry;
            if (is_dir($from)) {
                $this->copyTree($from, $to);
                continue;
            }

            if (!copy($from, $to)) {
                throw new RuntimeException('Unable to copy bundled theme file: ' . $from);
            }
        }
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir . '/' . $entry;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
