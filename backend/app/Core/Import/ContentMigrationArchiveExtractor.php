<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Import;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\Security\Services\ZipEntryGuard;

/**
 * Extracts CMS migration uploads (ZIP) to a temp directory with Zip-Slip protection.
 */
final class ContentMigrationArchiveExtractor
{
    public function __construct(
        private ZipEntryGuard $zipGuard,
    ) {
    }

    /**
     * @return array{extractDir: string, format: string, importPath: string}
     */
    public function extractZip(string $zipPath): array
    {
        if (!is_file($zipPath)) {
            throw new FlatFileException('Archive file not found');
        }

        $extractDir = sys_get_temp_dir() . '/paginium_cms_import_' . bin2hex(random_bytes(8));
        if (!mkdir($extractDir, 0700, true) && !is_dir($extractDir)) {
            throw new FlatFileException('Failed to create temp directory for import');
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new FlatFileException('Invalid ZIP archive');
        }

        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $entryName = (string) $zip->getNameIndex($i);
            if (!$this->zipGuard->isSafeEntry($entryName)) {
                $zip->close();
                $this->removeDirectory($extractDir);
                throw new FlatFileException('Unsafe path in migration archive');
            }
        }

        if (!$zip->extractTo($extractDir)) {
            $zip->close();
            $this->removeDirectory($extractDir);
            throw new FlatFileException('Failed to extract migration archive');
        }

        $zip->close();

        $detected = $this->detectFormat($extractDir);
        if ($detected === null) {
            $this->removeDirectory($extractDir);
            throw new FlatFileException('Could not detect CMS format in archive (expected WordPress WXR, Ghost JSON, Grav user/pages, Jekyll, or Hugo content tree)');
        }

        return [
            'extractDir' => $extractDir,
            'format' => $detected['format'],
            'importPath' => $detected['path'],
        ];
    }

    public function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }

        @rmdir($dir);
    }

    /**
     * @return array{format: string, path: string}|null
     */
    public function detectFormat(string $root): ?array
    {
        $root = rtrim($root, '/\\');

        $wxr = $this->findFileByExtension($root, 'xml');
        if ($wxr !== null && $this->looksLikeWordPressExport($wxr)) {
            return ['format' => 'wordpress', 'path' => $wxr];
        }

        $json = $this->findGhostExport($root);
        if ($json !== null) {
            return ['format' => 'ghost', 'path' => $json];
        }

        $gravPages = $this->findNestedDirectory($root, 'user/pages');
        if ($gravPages !== null) {
            return ['format' => 'grav', 'path' => $gravPages];
        }

        $pagesOnly = $this->findNestedDirectory($root, 'pages');
        if ($pagesOnly !== null && $this->directoryHasMarkdown($pagesOnly)) {
            return ['format' => 'grav', 'path' => $pagesOnly];
        }

        if (is_dir($root . '/_posts') || $this->findNestedDirectory($root, '_posts') !== null) {
            return ['format' => 'jekyll', 'path' => $root];
        }

        $hugoContent = $this->findNestedDirectory($root, 'content');
        if ($hugoContent !== null && $this->directoryHasMarkdown($hugoContent)) {
            return ['format' => 'hugo', 'path' => $hugoContent];
        }

        if ($this->directoryHasMarkdown($root)) {
            return ['format' => 'jekyll', 'path' => $root];
        }

        return null;
    }

    private function looksLikeWordPressExport(string $path): bool
    {
        $head = is_readable($path) ? (string) file_get_contents($path, false, null, 0, 4096) : '';

        return str_contains($head, 'wordpress.org/export')
            || (str_contains($head, '<rss') && str_contains($head, 'wp:post_type'));
    }

    private function findGhostExport(string $root): ?string
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'json') {
                continue;
            }

            $head = (string) file_get_contents($file->getPathname(), false, null, 0, 2048);
            if (str_contains($head, '"posts"') || str_contains($head, '"db"')) {
                return $file->getPathname();
            }
        }

        return null;
    }

    private function findFileByExtension(string $root, string $extension): ?string
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === $extension) {
                return $file->getPathname();
            }
        }

        return null;
    }

    private function findNestedDirectory(string $root, string $relative): ?string
    {
        $relative = trim(str_replace('\\', '/', $relative), '/');
        $direct = $root . '/' . $relative;
        if (is_dir($direct)) {
            return $direct;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isDir()) {
                continue;
            }

            $path = str_replace('\\', '/', $file->getPathname());
            if (str_ends_with($path, '/' . $relative)) {
                return $path;
            }
        }

        return null;
    }

    private function directoryHasMarkdown(string $dir): bool
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with(strtolower($file->getFilename()), '.md')) {
                return true;
            }
        }

        return false;
    }
}
