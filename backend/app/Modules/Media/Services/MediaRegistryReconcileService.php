<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface;
use PaginiumCMS\Support\JsonHelper;

/**
 * Compares media/registry.json with binaries on disk (common after inconsistent backup restore).
 */
final class MediaRegistryReconcileService
{
    private const MEDIA_ROOT = 'media';
    private const REGISTRY = 'media/registry.json';
    private const FOLDER_MARKER = '.paginium-folder';

    public function __construct(
        private MediaRepositoryInterface $mediaRepository,
        private FileReaderInterface $reader,
    ) {
    }

    /**
     * @return array{
     *     healthy: bool,
     *     registryEntries: int,
     *     registryMissingFile: list<string>,
     *     diskWithoutRegistry: list<string>
     * }
     */
    public function report(): array
    {
        $files = $this->mediaRepository->findAll();
        $registryMissingFile = [];
        $registryPaths = [];

        foreach ($files as $file) {
            $path = $file->getPath();
            $registryPaths[$path] = true;
            if (!$this->reader->exists($path)) {
                $registryMissingFile[] = $path;
            }
        }

        sort($registryMissingFile);

        $diskWithoutRegistry = [];
        foreach ($this->walkMediaFilesOnDisk() as $relativePath) {
            if (!isset($registryPaths[$relativePath])) {
                $diskWithoutRegistry[] = $relativePath;
            }
        }

        return [
            'healthy' => $registryMissingFile === [] && $diskWithoutRegistry === [],
            'registryEntries' => count($files),
            'registryMissingFile' => $registryMissingFile,
            'diskWithoutRegistry' => $diskWithoutRegistry,
        ];
    }

    /**
     * Remove registry rows whose binary no longer exists (does not delete content references).
     */
    public function pruneRegistryMissingFiles(): int
    {
        $registry = $this->loadRegistryRaw();
        if ($registry === []) {
            return 0;
        }

        $kept = [];
        $removed = 0;
        foreach ($registry as $entry) {
            $path = (string) ($entry['path'] ?? '');
            if ($path === '' || !$this->reader->exists($path)) {
                ++$removed;
                continue;
            }
            $kept[] = $entry;
        }

        if ($removed > 0) {
            $this->saveRegistryRaw($kept);
        }

        return $removed;
    }

    /**
     * @return list<array<int|string, mixed>>
     */
    private function loadRegistryRaw(): array
    {
        if (!$this->reader->exists(self::REGISTRY)) {
            return [];
        }

        try {
            $decoded = JsonHelper::decode($this->reader->read(self::REGISTRY));

            return array_values(array_filter($decoded, static fn (mixed $row): bool => is_array($row)));
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param list<array<int|string, mixed>> $registry
     */
    private function saveRegistryRaw(array $registry): void
    {
        $json = JsonHelper::encode($registry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $base = rtrim($this->reader->getBasePath(), '/');
        $absolute = $base . '/' . self::REGISTRY;
        $dir = dirname($absolute);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($absolute, $json);
    }

    /**
     * @return list<string> Paths relative to content root (e.g. media/foo.jpg).
     */
    private function walkMediaFilesOnDisk(): array
    {
        $base = rtrim($this->reader->getBasePath(), '/');
        $root = $base . '/' . self::MEDIA_ROOT;
        if (!is_dir($root)) {
            return [];
        }

        $found = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo instanceof \SplFileInfo || !$fileInfo->isFile()) {
                continue;
            }
            $basename = $fileInfo->getFilename();
            if ($basename === self::FOLDER_MARKER || $basename === 'registry.json' || $basename === 'folders.json') {
                continue;
            }
            $absolute = $fileInfo->getPathname();
            $relative = self::MEDIA_ROOT . substr($absolute, strlen($root));
            $relative = str_replace('\\', '/', $relative);
            $found[] = $relative;
        }

        sort($found);

        return $found;
    }
}
