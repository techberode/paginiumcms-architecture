<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\I18n\Services;

use RuntimeException;

/**
 * Scaffolds backend/frontend translation files for a new locale (It.19c).
 */
final class LocaleScaffoldService
{
    private string $projectRoot;

    public function __construct(
        private SupportedLocalesRegistry $locales,
        ?string $projectRoot = null
    ) {
        $this->projectRoot = rtrim($projectRoot ?? dirname(__DIR__, 5), '/');
    }

    /**
     * @return list<string> Created relative paths
     */
    public function scaffold(string $code, string $copyFrom = 'sk'): array
    {
        $code = strtolower(trim($code));
        if (!$this->locales->isValidCode($code)) {
            throw new RuntimeException('Invalid locale code');
        }

        if ($this->locales->isSupported($code)) {
            throw new RuntimeException('Locale already exists');
        }

        $copyFrom = strtolower(trim($copyFrom));
        if (!$this->locales->isSupported($copyFrom)) {
            $copyFrom = 'sk';
        }

        $created = [];
        $created = [...$created, ...$this->scaffoldBackendLang($code, $copyFrom)];
        $created = [...$created, ...$this->scaffoldFrontendCore($code, $copyFrom)];
        $created = [...$created, ...$this->scaffoldFrontendModules($code, $copyFrom)];

        return $created;
    }

    /**
     * @return list<string>
     */
    private function scaffoldBackendLang(string $code, string $copyFrom): array
    {
        $sourceDir = $this->projectRoot . '/backend/lang/' . $copyFrom;
        $targetDir = $this->projectRoot . '/backend/lang/' . $code;
        if (!is_dir($sourceDir)) {
            return [];
        }

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $created = [];
        foreach (glob($sourceDir . '/*.php') ?: [] as $sourceFile) {
            $name = basename($sourceFile);
            $target = $targetDir . '/' . $name;
            if (is_file($target)) {
                continue;
            }
            $content = (string) file_get_contents($sourceFile);
            file_put_contents($target, $content);
            $created[] = 'backend/lang/' . $code . '/' . $name;
        }

        return $created;
    }

    /**
     * @return list<string>
     */
    private function scaffoldFrontendCore(string $code, string $copyFrom): array
    {
        $source = $this->projectRoot . '/frontend/src/i18n/core/' . $copyFrom . '.ts';
        $targetRelative = 'frontend/src/i18n/core/' . $code . '.ts';
        $target = $this->projectRoot . '/' . $targetRelative;

        if (!is_file($source) || is_file($target)) {
            return is_file($target) ? [] : [$this->writeMinimalCore($code, $targetRelative, $target)];
        }

        file_put_contents($target, (string) file_get_contents($source));

        return [$targetRelative];
    }

    /**
     * @return list<string>
     */
    private function scaffoldFrontendModules(string $code, string $copyFrom): array
    {
        $modulesDir = $this->projectRoot . '/frontend/src/i18n/modules';
        if (!is_dir($modulesDir)) {
            return [];
        }

        $created = [];
        foreach (scandir($modulesDir) ?: [] as $module) {
            if ($module === '.' || $module === '..') {
                continue;
            }

            $modulePath = $modulesDir . '/' . $module;
            if (!is_dir($modulePath)) {
                continue;
            }

            $source = $modulePath . '/' . $copyFrom . '.ts';
            $targetRelative = 'frontend/src/i18n/modules/' . $module . '/' . $code . '.ts';
            $target = $this->projectRoot . '/' . $targetRelative;

            if (is_file($target)) {
                continue;
            }

            if (is_file($source)) {
                file_put_contents($target, (string) file_get_contents($source));
            } else {
                $export = $this->exportName($module, $code);
                $content = <<<TS
import type { MessageTree } from '../../types';

/** {$module} module ({$code}) — translate from {$copyFrom}. */
export const {$export}: MessageTree = {};

TS;
                file_put_contents($target, $content);
            }

            $created[] = $targetRelative;
        }

        return $created;
    }

    private function writeMinimalCore(string $code, string $targetRelative, string $target): string
    {
        $export = 'core' . ucfirst($code);
        $content = <<<TS
import type { MessageTree } from '../types';

export const {$export}: MessageTree = {};

TS;
        $dir = dirname($target);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($target, $content);

        return $targetRelative;
    }

    private function exportName(string $module, string $locale): string
    {
        $suffix = str_replace('-', '', ucfirst($locale));

        return $module . $suffix;
    }
}
