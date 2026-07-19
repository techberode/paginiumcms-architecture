<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Demo\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Modules\Demo\Data\DemoFixtures;
use RuntimeException;

/**
 * Isolated demo flat-file storage under `storage/app/demo/` (Iteration 13).
 */
final class DemoStorageService
{
    private string $demoBasePath;
    private string $contentBasePath;

    public function __construct(
        private DemoMode $demoMode,
        FileReaderInterface $reader
    ) {
        $this->contentBasePath = rtrim($reader->getBasePath(), '/');
        $this->demoBasePath = dirname($this->contentBasePath) . '/demo';
    }

    public function isEnabled(): bool
    {
        return $this->demoMode->isEnabled();
    }

    public function demoBasePath(): string
    {
        return $this->demoBasePath;
    }

    public function contentBasePath(): string
    {
        return $this->contentBasePath;
    }

    /**
     * @return array{enabled: bool, storage_path: string, content_path: string, file_count: int, seeded: bool}
     */
    public function status(): array
    {
        $files = $this->listDemoFiles();

        return [
            'enabled' => $this->demoMode->isEnabled(),
            'storage_path' => $this->demoBasePath,
            'content_path' => $this->contentBasePath,
            'file_count' => count($files),
            'seeded' => $files !== [],
        ];
    }

    /**
     * @return array{written: int, storage_path: string}
     */
    public function reset(): array
    {
        if (!$this->demoMode->isEnabled()) {
            throw new RuntimeException('Demo mode is disabled');
        }

        $this->assertIsolatedFromProduction();
        $this->clearDemoDirectory();

        $written = 0;
        foreach (DemoFixtures::seedFiles() as $relativePath => $contents) {
            $this->writeDemoFile($relativePath, $contents);
            ++$written;
        }

        return [
            'written' => $written,
            'storage_path' => $this->demoBasePath,
        ];
    }

    /**
     * @return list<string>
     */
    public function listDemoFiles(): array
    {
        if (!is_dir($this->demoBasePath)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->demoBasePath, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $files[] = $file->getPathname();
        }

        sort($files);

        return $files;
    }

    public function assertIsolatedFromProduction(): void
    {
        $demoReal = realpath($this->demoBasePath) ?: $this->demoBasePath;
        $contentReal = realpath($this->contentBasePath) ?: $this->contentBasePath;

        if ($demoReal === $contentReal) {
            throw new RuntimeException('Demo storage must not overlap production content path');
        }

        if (str_starts_with($demoReal, $contentReal . '/') || str_starts_with($contentReal, $demoReal . '/')) {
            throw new RuntimeException('Demo storage path must be isolated from production content');
        }
    }

    private function clearDemoDirectory(): void
    {
        if (!is_dir($this->demoBasePath)) {
            if (!mkdir($this->demoBasePath, 0755, true) && !is_dir($this->demoBasePath)) {
                throw new RuntimeException('Cannot create demo directory: ' . $this->demoBasePath);
            }

            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->demoBasePath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
                continue;
            }

            if (basename($item->getPathname()) === '.gitkeep') {
                continue;
            }

            unlink($item->getPathname());
        }
    }

    private function writeDemoFile(string $relativePath, string $contents): void
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        $absolutePath = $this->demoBasePath . '/' . $relativePath;
        $dir = dirname($absolutePath);

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Cannot create demo subdirectory: ' . $dir);
        }

        if (file_put_contents($absolutePath, $contents) === false) {
            throw new RuntimeException('Cannot write demo file: ' . $absolutePath);
        }
    }
}
