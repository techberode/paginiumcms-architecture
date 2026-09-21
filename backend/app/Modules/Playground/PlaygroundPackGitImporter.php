<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Playground;

use JsonException;
use PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException;
use PaginiumCMS\Core\CodePolicy\Services\UntrustedPolicyScanner;
use PaginiumCMS\Core\Security\Services\ZipEntryGuard;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Playground\Contracts\PlaygroundArchiveDownloaderInterface;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;
use ZipArchive;

/**
 * Explicit Git/ZIP import for playground packs (It.95d). No cron, no npm on PHP.
 */
final class PlaygroundPackGitImporter
{
    private const MAX_ARCHIVE_ENTRIES = 400;
    private const MAX_ARCHIVE_BYTES = 5_000_000;

    public function __construct(
        private PlaygroundSettings $settings,
        private PlaygroundPackRegistry $registry,
        private PlaygroundArchiveDownloaderInterface $downloader,
        private ZipEntryGuard $zipGuard,
        private UntrustedPolicyScanner $scanner,
        private SettingsRepositoryInterface $settingsRepository,
    ) {
    }

    /**
     * @return array{packId: string, title: string, enabled: bool}
     */
    public function importFromSettings(): array
    {
        if ($this->settings->isDemoBlocked()) {
            throw new RuntimeException('playground_demo');
        }
        if (!$this->settings->gitConfigured()) {
            throw new RuntimeException('playground_git_not_configured');
        }

        $repoUrl = $this->settings->gitRepoUrl();
        $ref = $this->settings->gitRef();
        $zipUrl = PlaygroundGitArchiveLocator::zipUrl($repoUrl, $ref);
        $bytes = $this->downloader->download($zipUrl, $this->settings->gitToken());

        return $this->importZipBytes($bytes, $repoUrl, $ref);
    }

    /**
     * @return array{packId: string, title: string, enabled: bool}
     */
    public function importZipBytes(string $bytes, string $repoUrl, string $ref): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('ZipArchive PHP extension is required.');
        }
        if ($bytes === '' || strlen($bytes) > self::MAX_ARCHIVE_BYTES) {
            throw new RuntimeException('Pack archive is empty or too large.');
        }

        $tempZip = sys_get_temp_dir() . '/pag_playground_pack_' . uniqid('', true) . '.zip';
        $tempDir = sys_get_temp_dir() . '/pag_playground_pack_' . uniqid('', true);
        if (file_put_contents($tempZip, $bytes) === false) {
            throw new RuntimeException('Unable to stage pack archive.');
        }
        if (!mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
            @unlink($tempZip);
            throw new RuntimeException('Unable to stage pack extract.');
        }

        try {
            $this->extractZip($tempZip, $tempDir);
            [$packRoot, $manifest] = $this->resolvePackRoot($tempDir);
            $packId = (string) ($manifest['packId'] ?? '');
            if ($this->registry->isBundledPack($packId)) {
                throw new RuntimeException('Cannot overwrite a bundled playground pack.');
            }

            $this->assertAllowedFiles($packRoot);
            $errors = $this->scanner->scanDirectory($packRoot, 'data/playground-packs/' . $packId);
            if ($errors !== []) {
                throw new CodePolicyViolationException($errors);
            }

            $target = $this->registry->importedPacksDirectory() . DIRECTORY_SEPARATOR . $packId;
            $this->replaceDirectory($packRoot, $target);
            $this->registry->registerImported([
                'packId' => $packId,
                'title' => (string) ($manifest['title'] ?? $packId),
                'source' => [
                    'type' => 'git',
                    'url' => $repoUrl,
                    'ref' => $ref,
                ],
                'modules' => is_array($manifest['modules'] ?? null) ? $manifest['modules'] : [],
                'root' => $target,
            ]);
            $enabled = $this->enablePack($packId);

            return [
                'packId' => $packId,
                'title' => (string) ($manifest['title'] ?? $packId),
                'enabled' => $enabled,
            ];
        } finally {
            @unlink($tempZip);
            $this->removeDir($tempDir);
        }
    }

    private function extractZip(string $zipPath, string $destination): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Unable to open pack ZIP.');
        }
        if ($zip->numFiles > self::MAX_ARCHIVE_ENTRIES) {
            $zip->close();
            throw new RuntimeException('Pack archive has too many entries.');
        }

        $uncompressedBytes = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = (string) $zip->getNameIndex($i);
            if (!$this->zipGuard->isSafeEntry($entryName)) {
                $zip->close();
                throw new RuntimeException('Unsafe ZIP entry rejected.');
            }
            $stat = $zip->statIndex($i);
            $size = is_array($stat) ? (int) $stat['size'] : 0;
            if ($size < 0 || $size > PlaygroundPackRegistry::MAX_FILE_BYTES) {
                $zip->close();
                throw new RuntimeException('Pack archive entry is too large.');
            }
            $uncompressedBytes += $size;
            if ($uncompressedBytes > PlaygroundPackRegistry::MAX_TOTAL_BYTES) {
                $zip->close();
                throw new RuntimeException('Pack archive expands beyond the allowed size.');
            }
            $opsys = 0;
            $attributes = 0;
            if (
                $zip->getExternalAttributesIndex($i, $opsys, $attributes)
                && (($attributes >> 16) & 0170000) === 0120000
            ) {
                $zip->close();
                throw new RuntimeException('Symbolic links are not allowed in pack archives.');
            }
        }

        if (!$zip->extractTo($destination)) {
            $zip->close();
            throw new RuntimeException('Unable to extract pack ZIP.');
        }
        $zip->close();
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function resolvePackRoot(string $extractDir): array
    {
        $atRoot = $extractDir . DIRECTORY_SEPARATOR . 'manifest.json';
        if (is_file($atRoot)) {
            return [$extractDir, $this->readManifest($atRoot)];
        }

        $entries = array_values(array_filter(
            scandir($extractDir) ?: [],
            static fn (string $entry): bool => !in_array($entry, ['.', '..'], true)
        ));
        if (count($entries) === 1) {
            $candidate = $extractDir . DIRECTORY_SEPARATOR . $entries[0];
            if (is_dir($candidate) && is_file($candidate . DIRECTORY_SEPARATOR . 'manifest.json')) {
                return [$candidate, $this->readManifest($candidate . DIRECTORY_SEPARATOR . 'manifest.json')];
            }
        }

        throw new RuntimeException('manifest.json not found in pack ZIP.');
    }

    /**
     * @return array<string, mixed>
     */
    private function readManifest(string $path): array
    {
        try {
            $decoded = JsonHelper::decode((string) file_get_contents($path));
        } catch (JsonException) {
            throw new RuntimeException('manifest.json is invalid JSON.');
        }

        $manifest = [];
        foreach ($decoded as $key => $value) {
            if (!is_string($key)) {
                throw new RuntimeException('manifest.json must be an object.');
            }
            $manifest[$key] = $value;
        }

        $packId = (string) ($manifest['packId'] ?? '');
        if (preg_match('/^[a-z0-9][a-z0-9-]{0,62}$/', $packId) !== 1) {
            throw new RuntimeException('manifest.json packId is invalid.');
        }
        if ((int) ($manifest['manifestVersion'] ?? 0) < 1) {
            throw new RuntimeException('manifest.json manifestVersion is required.');
        }

        return $manifest;
    }

    private function assertAllowedFiles(string $root): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );
        $fileCount = 0;
        $totalBytes = 0;
        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile()) {
                continue;
            }
            $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($root))), '/');
            $ext = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
            if ($ext === 'php') {
                throw new RuntimeException('PHP files are not allowed in playground packs.');
            }
            if ($relative !== 'manifest.json' && !in_array($ext, PlaygroundPackRegistry::ALLOWED_EXTENSIONS, true)) {
                continue;
            }
            $size = $file->getSize();
            if ($size > PlaygroundPackRegistry::MAX_FILE_BYTES) {
                throw new RuntimeException('Pack source file is too large.');
            }
            $fileCount++;
            $totalBytes += $size;
            if ($fileCount > PlaygroundPackRegistry::MAX_FILE_COUNT) {
                throw new RuntimeException('Pack has too many files.');
            }
            if ($totalBytes > PlaygroundPackRegistry::MAX_TOTAL_BYTES) {
                throw new RuntimeException('Pack source files exceed the total size limit.');
            }
        }
        if ($fileCount < 2) {
            throw new RuntimeException('Pack must include manifest.json and at least one source file.');
        }
    }

    private function replaceDirectory(string $source, string $target): void
    {
        if (is_dir($target)) {
            $this->removeDir($target);
        }
        if (!mkdir($target, 0775, true) && !is_dir($target)) {
            throw new RuntimeException('Unable to install playground pack.');
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo) {
                continue;
            }
            $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($source))), '/');
            if ($relative === '' || str_contains($relative, '..')) {
                continue;
            }
            $dest = $target . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            if ($file->isDir()) {
                if (!is_dir($dest) && !mkdir($dest, 0775, true) && !is_dir($dest)) {
                    throw new RuntimeException('Unable to install playground pack.');
                }
                continue;
            }
            $ext = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
            if ($relative !== 'manifest.json' && !in_array($ext, PlaygroundPackRegistry::ALLOWED_EXTENSIONS, true)) {
                continue;
            }
            if (!copy($file->getPathname(), $dest)) {
                throw new RuntimeException('Unable to install playground pack file.');
            }
        }
    }

    private function enablePack(string $packId): bool
    {
        $group = $this->settingsRepository->group('playground');
        $raw = (string) ($group['enabledPacks'] ?? 'paginium-starter');
        $ids = [];
        foreach (explode(',', $raw) as $part) {
            $id = strtolower(trim($part));
            if ($id !== '' && preg_match('/^[a-z0-9][a-z0-9-]{0,62}$/', $id) === 1) {
                $ids[] = $id;
            }
        }
        $ids[] = $packId;
        $ids = array_values(array_unique($ids));
        $group['enabledPacks'] = implode(',', $ids);
        $this->settingsRepository->setGroup('playground', $group);

        return true;
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo) {
                continue;
            }
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }
        @rmdir($dir);
    }
}
