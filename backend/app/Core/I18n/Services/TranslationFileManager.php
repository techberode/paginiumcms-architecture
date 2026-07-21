<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\I18n\Services;

use PaginiumCMS\Core\CodeEditor\Services\FileBackup;
use PaginiumCMS\Core\I18n\Contracts\TranslationFileManagerInterface;
use PaginiumCMS\Core\I18n\Exception\TranslationPolicyViolationException;
use RuntimeException;

/**
 * Scoped file access for backend/lang and frontend i18n catalogs (It.18d).
 */
final class TranslationFileManager implements TranslationFileManagerInterface
{
    /** @var list<string> */
    private array $locales = ['sk', 'en'];

    private string $projectRoot;
    private string $stagingDir;
    private string $rejectedDir;

    public function __construct(
        private TranslationPolicyValidator $policy,
        private FileBackup $backup,
        ?string $projectRoot = null
    ) {
        $this->projectRoot = rtrim($projectRoot ?? dirname(__DIR__, 5), '/');
        $this->stagingDir = $this->projectRoot . '/storage/translations/staging';
        $this->rejectedDir = $this->projectRoot . '/storage/translations/rejected';

        foreach ([$this->stagingDir, $this->rejectedDir] as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    public function listCatalog(): array
    {
        $files = [];

        foreach ($this->locales as $locale) {
            $langDir = $this->projectRoot . '/backend/lang/' . $locale;
            if (is_dir($langDir)) {
                foreach (glob($langDir . '/*.php') ?: [] as $absolute) {
                    $relative = $this->toRelativePath($absolute);
                    if ($relative === null) {
                        continue;
                    }

                    $module = pathinfo($relative, PATHINFO_FILENAME);
                    $files[] = $this->buildEntry($relative, 'backend', $locale, $module);
                }
            }

            foreach (['core', 'modules'] as $segment) {
                if ($segment === 'core') {
                    $relative = 'frontend/src/i18n/core/' . $locale . '.ts';
                    $full = $this->projectRoot . '/' . $relative;
                    if (is_file($full)) {
                        $files[] = $this->buildEntry($relative, 'frontend', $locale, 'core');
                    }
                    continue;
                }

                $modulesDir = $this->projectRoot . '/frontend/src/i18n/modules';
                if (!is_dir($modulesDir)) {
                    continue;
                }

                foreach (scandir($modulesDir) ?: [] as $moduleDir) {
                    if ($moduleDir === '.' || $moduleDir === '..') {
                        continue;
                    }

                    $relative = 'frontend/src/i18n/modules/' . $moduleDir . '/' . $locale . '.ts';
                    $full = $this->projectRoot . '/' . $relative;
                    if (is_file($full)) {
                        $files[] = $this->buildEntry($relative, 'frontend', $locale, $moduleDir);
                    }
                }
            }
        }

        usort(
            $files,
            static fn (array $a, array $b): int => [$a['source'], $a['locale'], $a['module']]
                <=> [$b['source'], $b['locale'], $b['module']]
        );

        return [
            'sources' => [
                ['id' => 'backend', 'label' => 'Backend (API)', 'locales' => $this->locales],
                ['id' => 'frontend', 'label' => 'Frontend (Admin UI)', 'locales' => $this->locales],
            ],
            'files' => $files,
        ];
    }

    public function canEdit(string $path): bool
    {
        $normalized = $this->normalizeRelativePath($path);
        if ($normalized === null) {
            return false;
        }

        if (preg_match('#^backend/lang/(sk|en)/[a-z0-9_-]+\.php$#', $normalized) === 1) {
            return true;
        }

        if (preg_match('#^frontend/src/i18n/core/(sk|en)\.ts$#', $normalized) === 1) {
            return true;
        }

        return preg_match('#^frontend/src/i18n/modules/[a-z0-9_-]+/(sk|en)\.ts$#', $normalized) === 1;
    }

    public function readFile(string $path): string
    {
        $fullPath = $this->resolveExistingPath($path);

        return (string) file_get_contents($fullPath);
    }

    public function writeFile(string $path, string $content): bool
    {
        $fullPath = $this->resolveExistingPath($path);
        $stagingPath = $this->stagingDir . '/' . md5($path) . '.tmp';

        if (file_put_contents($stagingPath, $content) === false) {
            throw new RuntimeException('Failed to stage translation file');
        }

        try {
            $this->policy->assertValid($path, $content);
        } catch (TranslationPolicyViolationException $e) {
            $rejectedPath = $this->storeRejectedCopy($path, $content);
            @unlink($stagingPath);
            throw new TranslationPolicyViolationException($e->getErrors(), $rejectedPath);
        }

        $this->backup->create($path);

        if (!rename($stagingPath, $fullPath)) {
            @unlink($stagingPath);
            throw new RuntimeException('Failed to promote staged translation file');
        }

        return true;
    }

    /**
     * @return list<array{code: string, message: string, line?: int, hint?: string}>
     */
    public function validateContent(string $path, string $content): array
    {
        if (!$this->canEdit($path)) {
            throw new RuntimeException('Access to path is denied');
        }

        return $this->policy->collectErrors($path, $content);
    }

    private function storeRejectedCopy(string $path, string $content): string
    {
        $safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '_', str_replace('/', '__', $path)) ?? 'rejected';
        $rejectedPath = $this->rejectedDir . '/' . $safeName . '.' . date('Y-m-d_H-i-s') . '.err';
        file_put_contents($rejectedPath, $content);

        return $rejectedPath;
    }

    public function getFileInfo(string $path): array
    {
        $fullPath = $this->resolveExistingPath($path);
        $relative = (string) $this->normalizeRelativePath($path);
        $extension = pathinfo($relative, PATHINFO_EXTENSION);

        return [
            'path' => $relative,
            'name' => basename($relative),
            'size' => (int) filesize($fullPath),
            'modified' => (int) filemtime($fullPath),
            'extension' => $extension,
            'language' => $this->detectLanguage($extension),
            'backups' => array_map('basename', $this->backup->getBackups($relative)),
        ];
    }

    public function getBackups(string $path): array
    {
        if (!$this->canEdit($path)) {
            throw new RuntimeException('Access to file is denied');
        }

        return array_map('basename', $this->backup->getBackups($path));
    }

    public function restoreBackup(string $path, string $backupBasename): bool
    {
        if (!$this->canEdit($path)) {
            throw new RuntimeException('Access to file is denied');
        }

        $backupPath = $this->backup->resolveBackupByBasename($path, $backupBasename);
        if ($backupPath === null) {
            throw new RuntimeException('Backup not found');
        }

        $content = file_get_contents($backupPath);
        if ($content === false) {
            throw new RuntimeException('Unable to read backup file');
        }

        return $this->writeFile($path, $content);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildEntry(string $relative, string $source, string $locale, string $module): array
    {
        $full = $this->projectRoot . '/' . $relative;
        $extension = pathinfo($relative, PATHINFO_EXTENSION);

        return [
            'path' => $relative,
            'source' => $source,
            'locale' => $locale,
            'module' => $module,
            'name' => basename($relative),
            'size' => (int) filesize($full),
            'modified' => (int) filemtime($full),
            'extension' => $extension,
            'language' => $this->detectLanguage($extension),
        ];
    }

    private function resolveExistingPath(string $path): string
    {
        if (!$this->canEdit($path)) {
            throw new RuntimeException('Access to path is denied');
        }

        $fullPath = $this->projectRoot . '/' . $this->normalizeRelativePath($path);
        $real = realpath($fullPath);
        if ($real === false || !str_starts_with($real, $this->projectRoot . '/') || !is_file($real)) {
            throw new RuntimeException('Translation file does not exist');
        }

        return $real;
    }

    private function normalizeRelativePath(string $path): ?string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, '..') || str_starts_with($path, '/')) {
            return null;
        }

        return $path;
    }

    private function toRelativePath(string $absolutePath): ?string
    {
        $real = realpath($absolutePath);
        if ($real === false || !str_starts_with($real, $this->projectRoot . '/')) {
            return null;
        }

        return substr($real, strlen($this->projectRoot) + 1);
    }

    private function detectLanguage(string $extension): string
    {
        return match (strtolower($extension)) {
            'php' => 'php',
            'ts' => 'typescript',
            default => 'plaintext',
        };
    }
}
