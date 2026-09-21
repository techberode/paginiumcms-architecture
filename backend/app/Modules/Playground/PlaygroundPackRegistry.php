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
    public const ALLOWED_EXTENSIONS = ['tsx', 'ts', 'jsx', 'js', 'css', 'json', 'html', 'md'];
    public const MAX_FILE_COUNT = 200;
    public const MAX_FILE_BYTES = 400_000;
    public const MAX_TOTAL_BYTES = 2_000_000;

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
        if ($content === false || strlen($content) > self::MAX_FILE_BYTES) {
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
            if (
                !is_array($pack)
                || !is_string($pack['packId'] ?? null)
                || preg_match('/^[a-z0-9][a-z0-9-]{0,62}$/', $pack['packId']) !== 1
                || !is_string($pack['root'] ?? null)
                || !$this->isImportedRootAllowed($pack['root'])
            ) {
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
        if (!is_dir($root)) {
            return [];
        }

        $files = [];
        $fileCount = 0;
        $totalBytes = 0;
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
            );
        } catch (\UnexpectedValueException) {
            return [];
        }
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
            $size = filesize($safe);
            if ($size === false || $size > self::MAX_FILE_BYTES) {
                return [];
            }
            $fileCount++;
            $totalBytes += $size;
            if ($fileCount > self::MAX_FILE_COUNT || $totalBytes > self::MAX_TOTAL_BYTES) {
                return [];
            }
            $content = file_get_contents($safe);
            if ($content === false) {
                return [];
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

    /**
     * @param array<string, mixed> $pack
     */
    public function registerImported(array $pack): void
    {
        $id = (string) ($pack['packId'] ?? '');
        $root = (string) ($pack['root'] ?? '');
        if ($id === '' || preg_match('/^[a-z0-9][a-z0-9-]{0,62}$/', $id) !== 1) {
            throw new \InvalidArgumentException('Invalid imported pack id.');
        }
        if (!$this->isImportedRootAllowed($root) && !$this->isImportedRootAllowedPending($root, $id)) {
            throw new \InvalidArgumentException('Imported pack root is outside the playground-packs directory.');
        }

        $existing = [];
        if (is_file($this->importedRegistryPath)) {
            try {
                $decoded = JsonHelper::decode(file_get_contents($this->importedRegistryPath) ?: '');
                if (isset($decoded['packs']) && is_array($decoded['packs'])) {
                    $existing = $decoded['packs'];
                }
            } catch (JsonException) {
                $existing = [];
            }
        }

        $packs = [];
        foreach ($existing as $row) {
            if (!is_array($row) || (string) ($row['packId'] ?? '') === $id) {
                continue;
            }
            $packs[] = $row;
        }
        $packs[] = [
            'packId' => $id,
            'title' => (string) ($pack['title'] ?? $id),
            'source' => is_array($pack['source'] ?? null) ? $pack['source'] : ['type' => 'git'],
            'modules' => is_array($pack['modules'] ?? null) ? $pack['modules'] : [],
            'root' => $root,
        ];

        $dir = dirname($this->importedRegistryPath);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Unable to write playground pack registry.');
        }

        $tmp = $this->importedRegistryPath . '.tmp';
        try {
            $json = JsonHelper::encode(['packs' => $packs]);
        } catch (JsonException) {
            throw new \RuntimeException('Unable to encode playground pack registry.');
        }
        if (file_put_contents($tmp, $json) === false || !rename($tmp, $this->importedRegistryPath)) {
            throw new \RuntimeException('Unable to write playground pack registry.');
        }
    }

    public function importedPacksDirectory(): string
    {
        return dirname($this->importedRegistryPath) . DIRECTORY_SEPARATOR . 'playground-packs';
    }

    public function isBundledPack(string $packId): bool
    {
        foreach ($this->bundledPacks() as $pack) {
            if ((string) ($pack['packId'] ?? '') === $packId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Allow a root that is about to be created under playground-packs/{id}.
     */
    private function isImportedRootAllowedPending(string $root, string $packId): bool
    {
        $base = $this->importedPacksDirectory();
        $expected = $base . DIRECTORY_SEPARATOR . $packId;
        $normalizedRoot = rtrim(str_replace('\\', '/', $root), '/');
        $normalizedExpected = rtrim(str_replace('\\', '/', $expected), '/');

        return $normalizedRoot === $normalizedExpected;
    }

    private function isImportedRootAllowed(string $root): bool
    {
        $base = realpath(dirname($this->importedRegistryPath) . DIRECTORY_SEPARATOR . 'playground-packs');
        $real = realpath($root);
        if ($base === false || $real === false || !is_dir($real)) {
            return false;
        }

        return str_starts_with($real, $base . DIRECTORY_SEPARATOR);
    }

    private function normalizeRelative(string $path): string
    {
        return ltrim(str_replace('\\', '/', $path), '/');
    }
}
