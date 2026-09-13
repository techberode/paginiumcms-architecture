<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\I18n\Services;

/**
 * Loads frontend i18n catalogs from disk for runtime merge in the SPA (post Translation Editor save).
 */
final class RuntimeI18nOverridesService
{
    private string $projectRoot;

    public function __construct(
        private TranslationMessageTreeParser $parser,
        private SupportedLocalesRegistry $locales,
        ?string $projectRoot = null
    ) {
        $this->projectRoot = rtrim($projectRoot ?? dirname(__DIR__, 5), '/');
    }

    /**
     * @return array{
     *   core: array<string, mixed>,
     *   modules: array<string, array<string, mixed>>,
     *   modified: int
     * }
     */
    public function loadFrontendCatalog(string $locale): array
    {
        $locale = strtolower(trim($locale));
        if (!$this->locales->isSupported($locale)) {
            return [
                'core' => [],
                'modules' => [],
                'modified' => 0,
            ];
        }

        $modified = 0;
        $core = [];
        $corePath = $this->projectRoot . '/frontend/src/i18n/core/' . $locale . '.ts';
        if (is_file($corePath)) {
            $modified = max($modified, (int) filemtime($corePath));
            $core = $this->parseFile($corePath);
        }

        $modules = [];
        $modulesDir = $this->projectRoot . '/frontend/src/i18n/modules';
        if (is_dir($modulesDir)) {
            foreach (scandir($modulesDir) ?: [] as $moduleDir) {
                if ($moduleDir === '.' || $moduleDir === '..') {
                    continue;
                }

                $path = $modulesDir . '/' . $moduleDir . '/' . $locale . '.ts';
                if (!is_file($path)) {
                    continue;
                }

                $modified = max($modified, (int) filemtime($path));
                $parsed = $this->parseFile($path);
                if ($parsed !== []) {
                    $modules[$moduleDir] = $parsed;
                }
            }
        }

        return [
            'core' => $core,
            'modules' => $modules,
            'modified' => $modified,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseFile(string $absolutePath): array
    {
        $content = file_get_contents($absolutePath);
        if (!is_string($content) || trim($content) === '') {
            return [];
        }

        try {
            return $this->parser->parseTypeScriptCatalog($content);
        } catch (\Throwable) {
            return [];
        }
    }
}
