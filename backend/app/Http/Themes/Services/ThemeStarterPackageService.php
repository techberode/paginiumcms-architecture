<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Themes\Services;

use RuntimeException;
use ZipArchive;

/**
 * Builds downloadable starter theme ZIP archives from bundled source trees.
 */
final class ThemeStarterPackageService
{
    /** @var list<string> */
    private const ALLOWED_IDS = [
        'clean-journal',
    ];

    public function __construct(
        private string $packagesRoot,
    ) {
        $this->packagesRoot = rtrim($packagesRoot, '/');
    }

    /**
     * @return list<string>
     */
    public function listAvailable(): array
    {
        return self::ALLOWED_IDS;
    }

    /**
     * Creates a temporary ZIP file; caller must unlink after streaming.
     */
    public function buildZipPath(string $id): string
    {
        $id = trim($id);
        if (!in_array($id, self::ALLOWED_IDS, true)) {
            throw new RuntimeException('Unknown starter theme package: ' . $id);
        }

        $sourceDir = $this->packagesRoot . '/' . $id;
        if (!is_dir($sourceDir) || !is_file($sourceDir . '/theme.json')) {
            throw new RuntimeException('Starter theme source is missing: ' . $id);
        }

        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('ZipArchive PHP extension is required.');
        }

        $zipPath = sys_get_temp_dir() . '/pag_theme_starter_' . $id . '_' . uniqid('', true) . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create starter theme ZIP.');
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $absolute = $item->getPathname();
            $relative = ltrim(str_replace('\\', '/', substr($absolute, strlen($sourceDir))), '/');
            if ($relative === '') {
                continue;
            }

            if ($item->isDir()) {
                $zip->addEmptyDir($id . '/' . $relative);
                continue;
            }

            $zip->addFile($absolute, $id . '/' . $relative);
        }

        $zip->close();

        return $zipPath;
    }
}
