<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Playground;

use JsonException;
use PaginiumCMS\Support\JsonHelper;

/**
 * Bundled + imported playground packs (It.95c). Imported packs live in data/playground-packs.json.
 */
final class PlaygroundPackRegistry
{
    private const ALLOWED_EXTENSIONS = ['tsx', 'ts', 'jsx', 'js', 'css', 'json', 'html', 'md'];

    public function __construct(
        private string $bundledPacksDir,
        private string $importedRegistryPath,
        private PlaygroundSettings $settings,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(bool $includeFiles): array
    {
        $packs = [...$this->bundledPacks(), ...$this->importedPacks()];
        $out = [];
        foreach ($packs as $pack) {
            $id = (string) ($pack['packId'] ?? '');
            if ($id === '') {
                continue;
            }
            $enabled = $this->settings->isPackEnabled($id);
            $row = [
                'packId' => $id,
                'title' => (string) ($pack['title'] ?? $id),
                'source' => is_array($pack['source'] ?? null) ? $pack['source'] : ['type' => 'unknown'],
                'modules' => is_array($pack['modules'] ?? null) ? $pack['modules'] : [],
                'enabled' => $enabled,
            ];
            if ($includeFiles && $enabled && isset($pack['root']) && is_string($pack['root'])) {
                $row['files'] = $this->readPackFiles($pack['root']);
            }
            $out[] = $row;
        }

        return $out;
    }

    /**
     * @return array{path: string, content: string}|null
     */
    public function readAsset(string $packId, string $relativePath): ?array
    {
        if (!$this->settings->isEnabled() || !$this->settings->isPackEnabled($packId)) {
            return null;
        }

        $pack = $this->findPack($packId);
        if ($pack === null || !isset($pack['root']) || !is_string($pack['root'])) {
            return null;
        }

        $safe = $this->resolvePackFile($pack['root'], $relativePath);
        if ($safe === null) {
            return null;
        }

        $content = file_get_contents($safe);
        if ($content === false) {
            return null;
        }

        return [
            'path' => $this->normalizeRelative($relativePath),
            'content' => $content,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findPack(string $packId): ?array
    {
        foreach ([...$this->bundledPacks(), ...$this->importedPacks()] as $pack) {
            if (($pack['packId'] ?? '') === $packId) {
                return $pack;
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function bundledPacks(): array
    {
        $root = $this->bundledPacksDir;
        if (!is_dir($root)) {
            return [];
        }

        $packs = [];
        $entries = scandir($root);
        if ($entries === false) {
            return [];
        }

        foreach ($entries as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $dir = $root . DIRECTORY_SEPARATOR . $name;
            $manifestPath = $dir . DIRECTORY_SEPARATOR . 'manifest.json';
            if (!is_dir($dir) || !is_file($manifestPath)) {
                continue;
            }
            try {
                $decoded = JsonHelper::decode(file_get_contents($manifestPath) ?: '');
            } catch (JsonException) {
                continue;
            }
            if (($decoded['packId'] ?? '') !== $name) {
                continue;
            }
            $packs[] = [
                'packId' => (string) $decoded['packId'],
                'title' => (string) ($decoded['title'] ?? $name),
                'source' => is_array($decoded['source'] ?? null) ? $decoded['source'] : ['type' => 'bundled'],
                'modules' => is_array($decoded['modules'] ?? null) ? $decoded['modules'] : [],
                'root' => $dir,
            ];
        }

        return $packs;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function importedPacks(): array
    {
        if (!is_file($this->importedRegistryPath)) {
            return [];
        }

        try {
            $decoded = JsonHelper::decode(file_get_contents($this->importedRegistryPath) ?: '');
        } catch (JsonException) {
            return [];
        }
        if (!isset($decoded['packs']) || !is_array($decoded['packs'])) {
            return [];
        }

        $packs = [];
        foreach ($decoded['packs'] as $pack) {
            if (!is_array($pack) || !is_string($pack['packId'] ?? null)) {
                continue;
            }
            $packs[] = $pack;
        }

        return $packs;
    }

    /**
     * @return array<string, string>
     */
    private function readPackFiles(string $root): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile()) {
                continue;
            }
            $absolute = $file->getPathname();
            $relative = ltrim(substr($absolute, strlen($root)), DIRECTORY_SEPARATOR);
            if (str_replace('\\', '/', $relative) === 'manifest.json') {
                continue;
            }
            $safe = $this->resolvePackFile($root, $relative);
            if ($safe === null) {
                continue;
            }
            $content = file_get_contents($safe);
            if ($content === false) {
                continue;
            }
            $files['/' . $this->normalizeRelative($relative)] = $content;
        }

        return $files;
    }

    private function resolvePackFile(string $root, string $relativePath): ?string
    {
        $relative = $this->normalizeRelative($relativePath);
        if ($relative === '' || $relative === 'manifest.json' || str_contains($relative, '..') || str_contains($relative, "\0")) {
            return null;
        }

        $ext = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            return null;
        }

        $base = realpath($root);
        $candidate = $base === false ? '' : $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $real = $candidate !== '' ? realpath($candidate) : false;
        if ($base === false || $real === false) {
            return null;
        }
        if (!str_starts_with($real, $base . DIRECTORY_SEPARATOR) && $real !== $base) {
            return null;
        }

        return $real;
    }

    private function normalizeRelative(string $path): string
    {
        return ltrim(str_replace('\\', '/', $path), '/');
    }
}
