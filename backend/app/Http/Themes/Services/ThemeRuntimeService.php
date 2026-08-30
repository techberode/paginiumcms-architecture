<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Themes\Services;

use PaginiumCMS\Http\Themes\Models\ThemeRecord;
use PaginiumCMS\Core\Cache\ContentCacheService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Active theme resolution and activation (It.83a–83b).
 */
final class ThemeRuntimeService
{
    public const CORE_THEME_ID = 'paginium-core';

    public function __construct(
        private SettingsRepositoryInterface $settings,
        private ThemeRegistry $registry,
        private ContentCacheService $contentCache,
        private string $themesRoot,
    ) {
        $this->themesRoot = rtrim($themesRoot, '/');
    }

    public function resolveActiveThemeId(): string
    {
        $appearance = $this->settings->group('appearance');
        $requested = trim((string) ($appearance['activeThemeId'] ?? self::CORE_THEME_ID));

        if ($requested === '' || $requested === self::CORE_THEME_ID) {
            return self::CORE_THEME_ID;
        }

        if (!$this->isValidThemeId($requested)) {
            return self::CORE_THEME_ID;
        }

        if (!$this->isThemePresent($requested)) {
            return self::CORE_THEME_ID;
        }

        return $requested;
    }

    /**
     * @return array{activeThemeId: string, previousThemeId: string|null}
     */
    public function activate(string $id): array
    {
        $id = trim($id);
        if ($id === '') {
            throw new RuntimeException('Theme id is required.');
        }

        if ($id === self::CORE_THEME_ID) {
            return $this->applyActivation(self::CORE_THEME_ID);
        }

        if (!$this->isValidThemeId($id)) {
            throw new RuntimeException('Invalid theme id.');
        }

        if ($this->registry->get($id) === null) {
            throw new RuntimeException('Theme not found: ' . $id);
        }

        if (!$this->isThemePresent($id)) {
            throw new RuntimeException('Theme files missing on disk: ' . $id);
        }

        return $this->applyActivation($id);
    }

    /**
     * @return array{activeThemeId: string, previousThemeId: string|null}
     */
    public function deactivate(): array
    {
        return $this->applyActivation(self::CORE_THEME_ID);
    }

    /**
     * @return array{activeThemeId: string, previousThemeId: string|null}
     */
    public function rollback(): array
    {
        $appearance = $this->settings->group('appearance');
        $previous = trim((string) ($appearance['previousThemeId'] ?? ''));
        if ($previous === '' || $previous === self::CORE_THEME_ID) {
            return $this->deactivate();
        }

        return $this->activate($previous);
    }

    public function getPreviousThemeId(): ?string
    {
        $appearance = $this->settings->group('appearance');
        $previous = trim((string) ($appearance['previousThemeId'] ?? ''));

        return $previous !== '' ? $previous : null;
    }

    public function assertNotActive(string $id): void
    {
        if ($this->resolveActiveThemeId() === $id) {
            throw new RuntimeException('Cannot uninstall the active theme. Deactivate it first.');
        }
    }

    /**
     * @return array{activeThemeId: string, previousThemeId: string|null}
     */
    private function applyActivation(string $targetId): array
    {
        $appearance = $this->settings->group('appearance');
        $current = trim((string) ($appearance['activeThemeId'] ?? self::CORE_THEME_ID));
        if ($current === '') {
            $current = self::CORE_THEME_ID;
        }

        $previous = $current !== $targetId
            ? $current
            : trim((string) ($appearance['previousThemeId'] ?? ''));

        $this->settings->setGroup('appearance', [
            'activeThemeId' => $targetId,
            'previousThemeId' => $previous,
        ]);

        $this->applyManifestAppearanceDefaults($targetId);
        $this->syncRegistryEnabled($targetId);
        $this->contentCache->purgeAll();

        return [
            'activeThemeId' => $targetId,
            'previousThemeId' => $previous !== '' ? $previous : null,
        ];
    }

    private function syncRegistryEnabled(string $activeId): void
    {
        foreach ($this->registry->all() as $id => $record) {
            $enabled = $activeId !== self::CORE_THEME_ID && $id === $activeId;
            if ($record->enabled === $enabled) {
                continue;
            }

            $this->registry->upsert(new ThemeRecord(
                id: $record->id,
                enabled: $enabled,
                installedAt: $record->installedAt,
            ));
        }
    }

    private function isThemePresent(string $id): bool
    {
        return is_dir($this->themesRoot . '/' . $id)
            && is_file($this->themesRoot . '/' . $id . '/theme.json');
    }

    private function isValidThemeId(string $id): bool
    {
        return preg_match('/^[a-z][a-z0-9-]{0,63}$/', $id) === 1;
    }

    private function applyManifestAppearanceDefaults(string $targetId): void
    {
        if ($targetId === self::CORE_THEME_ID) {
            return;
        }

        $manifest = $this->readManifest($targetId);
        if ($manifest === []) {
            return;
        }

        $patch = [];
        $scheme = trim((string) ($manifest['defaultColorScheme'] ?? ''));
        if ($scheme !== '') {
            $patch['colorScheme'] = $scheme;
        }

        $mode = trim((string) ($manifest['defaultMode'] ?? ''));
        if (in_array($mode, ['light', 'dark', 'system'], true)) {
            $patch['mode'] = $mode;
        }

        if ($patch !== []) {
            $this->settings->setGroup('appearance', $patch);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function readManifest(string $id): array
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
}
