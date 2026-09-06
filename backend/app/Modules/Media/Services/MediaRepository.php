<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Models\MediaFile;
use PaginiumCMS\Core\Security\Services\UploadSecurityValidator;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface;
use PaginiumCMS\Modules\Media\Contracts\MediaStorageDriverInterface;
use PaginiumCMS\Modules\Media\MediaFormats;
use PaginiumCMS\Support\JsonHelper;
use PaginiumCMS\Support\Lang;

class MediaRepository implements MediaRepositoryInterface
{
    private const MEDIA_DIR = 'media';
    private const REGISTRY = 'media/registry.json';
    private const FOLDERS_INDEX = 'media/folders.json';
    private const FOLDER_MARKER = '.paginium-folder';

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private SettingsRepositoryInterface $settings,
        private UploadSecurityValidator $uploadSecurity,
        private MediaStorageFactory $storageFactory,
        private MediaImageOptimizer $imageOptimizer,
        private MediaOptimizePreviewStore $optimizePreviewStore,
    ) {
    }

    /**
     * @param array<int|string, mixed> $filters
     * @return array<int, MediaFile>
     */
    public function findAll(array $filters = []): array
    {
        $registry = $this->loadRegistry();
        $files = [];

        foreach ($registry as $entry) {
            $file = $this->hydrate($entry);
            if ($this->matchesFilters($file, $filters)) {
                $files[] = $file;
            }
        }

        usort($files, fn (MediaFile $a, MediaFile $b) => $b->getUploadedAt() <=> $a->getUploadedAt());

        return $files;
    }

    public function findByPath(string $path): ?MediaFile
    {
        foreach ($this->loadRegistry() as $entry) {
            if (($entry['path'] ?? '') === $path) {
                return $this->hydrate($entry);
            }
        }

        return null;
    }

    public function saveUpload(
        string $originalName,
        $contents,
        string $mimeType,
        string $altText = '',
        string $folder = ''
    ): MediaFile {
        $binary = is_resource($contents) ? stream_get_contents($contents) : $contents;
        if (!is_string($binary) || $binary === '') {
            throw new FlatFileException('Prázdny alebo neplatný súbor');
        }

        $this->uploadSecurity->assertFilenameAllowed($originalName);

        $allowedMimeTypes = $this->uploadSecurity->resolveAllowedMimeTypes($this->resolveMediaMimeTypes());
        $mimeType = MediaFormats::validate(
            $originalName,
            $binary,
            $mimeType,
            $allowedMimeTypes,
            $this->uploadSecurity->shouldScanMagicBytes()
        );

        $folder = $this->normalizeFolder($folder);
        $safeName = $this->sanitizeFileName($originalName);
        $media = new MediaFile();

        $prefix = self::MEDIA_DIR . ($folder !== '' ? '/' . $folder : '');
        $relativePath = $prefix . '/' . $media->getId() . '_' . $safeName;

        $maxBytes = $this->uploadSecurity->resolveMaxUploadBytes($this->resolveMediaMaxUploadBytes());
        if (strlen($binary) > $maxBytes) {
            throw new FlatFileException('Súbor presahuje maximálnu povolenú veľkosť');
        }

        $storage = $this->storage();
        $storage->put($relativePath, $binary);

        $media->setPath($relativePath);
        $media->setFileName($safeName);
        $media->setUrl($storage->publicUrl($relativePath));
        $media->setSizeBytes(strlen($binary));
        $media->setMimeType($mimeType);
        $media->setAltText($altText);
        $media->setFolder($folder);

        $registry = $this->loadRegistry();
        $registry[] = $media->jsonSerialize();
        $this->saveRegistry($registry);
        $this->writeSidecar($media);

        return $media;
    }

    public function delete(string $path): void
    {
        $registry = $this->loadRegistry();
        $found = false;

        foreach ($registry as $index => $entry) {
            if (($entry['path'] ?? '') !== $path) {
                continue;
            }

            $this->storage()->delete($path);

            $sidecar = $this->sidecarPath($path);
            if ($this->reader->exists($sidecar)) {
                $this->writer->delete($sidecar, true);
            }

            unset($registry[$index]);
            $found = true;
            break;
        }

        if (!$found) {
            throw new FlatFileException('Médium nebolo nájdené');
        }

        $this->saveRegistry(array_values($registry));
    }

    /**
     * @param list<string> $paths
     */
    public function bulkDelete(array $paths): int
    {
        $deleted = 0;

        foreach ($paths as $path) {
            if ($path === '') {
                continue;
            }

            try {
                $this->delete($path);
                ++$deleted;
            } catch (FlatFileException) {
                // Skip missing paths in bulk operations.
            }
        }

        return $deleted;
    }

    public function update(MediaFile $file): void
    {
        $registry = $this->loadRegistry();
        $updated = false;

        foreach ($registry as $index => $entry) {
            if (($entry['path'] ?? '') !== $file->getPath()) {
                continue;
            }

            $registry[$index] = $file->jsonSerialize();
            $updated = true;
            break;
        }

        if (!$updated) {
            throw new FlatFileException('Médium nebolo nájdené');
        }

        $this->saveRegistry($registry);
        $this->writeSidecar($file);
    }

    public function inspectRaster(string $path): array
    {
        $media = $this->requireMediaWithBinary($path);
        $binary = $this->readBinary($path);
        $info = $this->imageOptimizer->inspect($binary);

        return [
            'width' => $info['width'],
            'height' => $info['height'],
            'mimeType' => $info['mimeType'],
            'sizeBytes' => $media->getSizeBytes(),
        ];
    }

    public function readBinary(string $path): string
    {
        $storage = $this->storage();
        if (!$storage->exists($path)) {
            throw new FlatFileException('Médium nebolo nájdené');
        }

        return $storage->read($path);
    }

    public function optimizeRaster(string $path, ?int $targetWidth = null, ?int $targetHeight = null): array
    {
        $media = $this->requireMediaWithBinary($path);
        $result = $this->runOptimize($path, $media->getMimeType(), $targetWidth, $targetHeight);

        return $this->persistOptimizeResult($media, $result);
    }

    public function previewOptimizeRaster(
        string $path,
        string $ownerUserId,
        ?int $targetWidth = null,
        ?int $targetHeight = null,
    ): array {
        $media = $this->requireMediaWithBinary($path);
        $result = $this->runOptimize($path, $media->getMimeType(), $targetWidth, $targetHeight);

        $stats = $this->optimizeStatsPayload($result);
        $token = $this->optimizePreviewStore->store(
            $path,
            $ownerUserId,
            $result['mimeType'],
            $result['binary'],
            $stats
        );

        return array_merge(['previewToken' => $token], $stats);
    }

    public function applyOptimizePreview(string $path, string $previewToken, string $ownerUserId): array
    {
        $media = $this->requireMediaWithBinary($path);
        $preview = $this->optimizePreviewStore->consume($previewToken, $path, $ownerUserId);
        if ($preview === null) {
            throw new FlatFileException(Lang::get('optimize_preview_expired', [], 'media'));
        }

        $stats = $preview['stats'];

        $result = [
            'binary' => $preview['binary'],
            'mimeType' => $preview['mimeType'],
            'beforeBytes' => (int) ($stats['beforeBytes'] ?? $media->getSizeBytes()),
            'afterBytes' => (int) ($stats['afterBytes'] ?? strlen($preview['binary'])),
            'savedBytes' => (int) ($stats['savedBytes'] ?? 0),
            'savedPercent' => (float) ($stats['savedPercent'] ?? 0.0),
            'beforeWidth' => (int) ($stats['beforeWidth'] ?? 0),
            'beforeHeight' => (int) ($stats['beforeHeight'] ?? 0),
            'width' => (int) ($stats['width'] ?? 0),
            'height' => (int) ($stats['height'] ?? 0),
        ];

        return $this->persistOptimizeResult($media, $result);
    }

    public function readOptimizePreview(string $previewToken, string $ownerUserId): ?array
    {
        $preview = $this->optimizePreviewStore->readForUser($previewToken, $ownerUserId);
        if ($preview === null) {
            return null;
        }

        return [
            'mimeType' => $preview['mimeType'],
            'binary' => $preview['binary'],
            'mediaPath' => $preview['mediaPath'],
        ];
    }

    /**
     * @return array{
     *     binary: string,
     *     mimeType: string,
     *     beforeBytes: int,
     *     afterBytes: int,
     *     savedBytes: int,
     *     savedPercent: float,
     *     beforeWidth: int,
     *     beforeHeight: int,
     *     width: int,
     *     height: int
     * }
     */
    private function runOptimize(
        string $path,
        string $mimeType,
        ?int $targetWidth,
        ?int $targetHeight,
    ): array {
        $binary = $this->readBinary($path);

        return $this->imageOptimizer->optimize($binary, $mimeType, $targetWidth, $targetHeight);
    }

    /**
     * @param array{
     *     binary: string,
     *     mimeType: string,
     *     beforeBytes: int,
     *     afterBytes: int,
     *     savedBytes: int,
     *     savedPercent: float,
     *     beforeWidth: int,
     *     beforeHeight: int,
     *     width: int,
     *     height: int
     * } $result
     * @return array{
     *     media: array<string, mixed>,
     *     beforeBytes: int,
     *     afterBytes: int,
     *     savedBytes: int,
     *     savedPercent: float,
     *     beforeWidth: int,
     *     beforeHeight: int,
     *     width: int,
     *     height: int
     * }
     */
    private function persistOptimizeResult(MediaFile $media, array $result): array
    {
        $this->storage()->put($media->getPath(), $result['binary']);
        $media->setSizeBytes($result['afterBytes']);
        $media->setMimeType($result['mimeType']);
        $this->update($media);

        return array_merge(
            ['media' => $media->jsonSerialize()],
            $this->optimizeStatsPayload($result)
        );
    }

    /**
     * @param array{
     *     beforeBytes: int,
     *     afterBytes: int,
     *     savedBytes: int,
     *     savedPercent: float,
     *     beforeWidth: int,
     *     beforeHeight: int,
     *     width: int,
     *     height: int
     * } $result
     * @return array{
     *     beforeBytes: int,
     *     afterBytes: int,
     *     savedBytes: int,
     *     savedPercent: float,
     *     beforeWidth: int,
     *     beforeHeight: int,
     *     width: int,
     *     height: int
     * }
     */
    private function optimizeStatsPayload(array $result): array
    {
        return [
            'beforeBytes' => $result['beforeBytes'],
            'afterBytes' => $result['afterBytes'],
            'savedBytes' => $result['savedBytes'],
            'savedPercent' => $result['savedPercent'],
            'beforeWidth' => $result['beforeWidth'],
            'beforeHeight' => $result['beforeHeight'],
            'width' => $result['width'],
            'height' => $result['height'],
        ];
    }

    private function requireMediaWithBinary(string $path): MediaFile
    {
        $media = $this->findByPath($path);
        if ($media === null) {
            throw new FlatFileException('Médium nebolo nájdené');
        }

        if (!$this->storage()->exists($path)) {
            throw new FlatFileException('Médium nebolo nájdené');
        }

        return $media;
    }

    public function listFolders(): array
    {
        $folders = [''];

        foreach ($this->loadRegistry() as $entry) {
            $folder = (string) ($entry['folder'] ?? '');
            if ($folder !== '' && !in_array($folder, $folders, true)) {
                $folders[] = $folder;
            }
        }

        foreach ($this->loadFolderIndex() as $folder) {
            if ($folder !== '' && !in_array($folder, $folders, true)) {
                $folders[] = $folder;
            }
        }

        sort($folders);

        return array_values(array_unique($folders));
    }

    public function createFolder(string $folder): void
    {
        $folder = $this->normalizeFolder($folder);
        if ($folder === '') {
            throw new FlatFileException('Neplatný názov priečinka');
        }

        $marker = self::MEDIA_DIR . '/' . $folder . '/' . self::FOLDER_MARKER;
        if (!$this->reader->exists($marker)) {
            $payload = JsonHelper::encode(['createdAt' => time()], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $this->writer->write($marker, $payload, true);
        }

        $folders = $this->loadFolderIndex();
        if (!in_array($folder, $folders, true)) {
            $folders[] = $folder;
            sort($folders);
            $this->saveFolderIndex($folders);
        }
    }

    /**
     * @return array<int, array<int|string, mixed>>
     */
    private function loadRegistry(): array
    {
        if (!$this->reader->exists(self::REGISTRY)) {
            return [];
        }

        try {
            $content = $this->reader->read(self::REGISTRY);
            $data = json_decode($content, true);

            return is_array($data) ? $data : [];
        } catch (FlatFileException) {
            return [];
        }
    }

    /**
     * @param array<int, array<int|string, mixed>> $registry
     */
    private function saveRegistry(array $registry): void
    {
        $json = JsonHelper::encode($registry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $this->writer->write(self::REGISTRY, $json, true);
    }

    /**
     * @param array<int|string, mixed> $entry
     */
    private function hydrate(array $entry): MediaFile
    {
        $file = new MediaFile();
        $reflection = new \ReflectionClass($file);

        foreach (['id', 'path', 'fileName', 'url', 'sizeBytes', 'mimeType', 'uploadedAt', 'altText', 'folder', 'title'] as $property) {
            if (!array_key_exists($property, $entry)) {
                continue;
            }

            $prop = $reflection->getProperty($property);
            $prop->setValue($file, $entry[$property]);
        }

        $this->mergeSidecar($file);

        return $file;
    }

    private function mergeSidecar(MediaFile $file): void
    {
        $sidecar = $this->sidecarPath($file->getPath());
        if (!$this->reader->exists($sidecar)) {
            return;
        }

        try {
            $content = $this->reader->read($sidecar);
            $data = json_decode($content, true);
            if (!is_array($data)) {
                return;
            }

            if (array_key_exists('altText', $data)) {
                $file->setAltText((string) $data['altText']);
            }
            if (array_key_exists('title', $data)) {
                $file->setTitle((string) $data['title']);
            }
            if (array_key_exists('folder', $data)) {
                $file->setFolder((string) $data['folder']);
            }
        } catch (FlatFileException) {
            // Ignore corrupt sidecars; registry remains source of truth for file identity.
        }
    }

    private function writeSidecar(MediaFile $file): void
    {
        $payload = [
            'altText' => $file->getAltText(),
            'title' => $file->getTitle(),
            'folder' => $file->getFolder(),
            'updatedAt' => time(),
        ];

        $json = JsonHelper::encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $this->writer->write($this->sidecarPath($file->getPath()), $json, true);
    }

    private function sidecarPath(string $path): string
    {
        return $path . '.meta.json';
    }

    /**
     * @param array<int|string, mixed> $filters
     */
    private function matchesFilters(MediaFile $file, array $filters): bool
    {
        if (empty($filters)) {
            return true;
        }

        if (isset($filters['folder']) && $file->getFolder() !== (string) $filters['folder']) {
            return false;
        }

        if (isset($filters['mimeType']) && $file->getMimeType() !== $filters['mimeType']) {
            return false;
        }

        if (isset($filters['type']) && $filters['type'] === 'image' && !MediaFormats::isImageMime($file->getMimeType())) {
            return false;
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private function loadFolderIndex(): array
    {
        if (!$this->reader->exists(self::FOLDERS_INDEX)) {
            return [];
        }

        try {
            $content = $this->reader->read(self::FOLDERS_INDEX);
            $data = json_decode($content, true);

            if (!is_array($data)) {
                return [];
            }

            return array_values(array_filter(
                array_map(static fn ($folder): string => is_string($folder) ? trim($folder, '/') : '', $data),
                static fn (string $folder): bool => $folder !== ''
            ));
        } catch (FlatFileException) {
            return [];
        }
    }

    /**
     * @param list<string> $folders
     */
    private function saveFolderIndex(array $folders): void
    {
        $json = JsonHelper::encode($folders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $this->writer->write(self::FOLDERS_INDEX, $json, true);
    }

    private function sanitizeFileName(string $name): string
    {
        $name = basename($name);
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '-', $name) ?? 'upload.bin';

        return $name !== '' ? $name : 'upload.bin';
    }

    /**
     * @return list<string>
     */
    public function resolveAllowedMimeTypes(): array
    {
        return $this->uploadSecurity->resolveAllowedMimeTypes($this->resolveMediaMimeTypes());
    }

    /**
     * @return array{
     *     mimeTypes: list<string>,
     *     extensions: list<string>,
     *     accept: string,
     *     previewableMimeTypes: list<string>
     * }
     */
    /**
     * @return array{
     *     mimeTypes: list<string>,
     *     extensions: list<string>,
     *     accept: string,
     *     previewableMimeTypes: list<string>,
     *     imageOptimization: array{
     *         available: bool,
     *         jpeg: bool,
     *         png: bool,
     *         webp: bool
     *     }
     * }
     */
    public function formatsPayload(): array
    {
        return array_merge(
            MediaFormats::toApiPayload($this->resolveAllowedMimeTypes()),
            ['imageOptimization' => MediaImageOptimizer::capabilities()]
        );
    }

    /**
     * @return list<string>
     */
    private function resolveMediaMimeTypes(): array
    {
        $raw = (string) ($this->settings->group('media')['allowedMimeTypes'] ?? '');
        if ($raw === '') {
            return MediaFormats::defaultMimeTypes();
        }

        $types = array_values(array_filter(
            array_map('trim', explode(',', $raw)),
            static fn (string $type): bool => $type !== '' && MediaFormats::isKnownMime($type)
        ));

        return $types !== [] ? $types : MediaFormats::defaultMimeTypes();
    }

    private function resolveMediaMaxUploadBytes(): int
    {
        $maxKb = (int) ($this->settings->group('media')['maxUploadSizeKb'] ?? 5120);

        return max(64, $maxKb) * 1024;
    }


    private function normalizeFolder(string $folder): string
    {
        $folder = trim(str_replace('\\', '/', $folder), '/');
        if ($folder === '' || str_contains($folder, '..')) {
            return '';
        }

        if (!preg_match('#^[a-zA-Z0-9][a-zA-Z0-9/_-]*$#', $folder)) {
            throw new FlatFileException('Neplatná cesta priečinka');
        }

        return $folder;
    }

    private function storage(): MediaStorageDriverInterface
    {
        return $this->storageFactory->create(
            MediaStorageFactory::driverFromMediaSettings($this->settings->group('media'))
        );
    }
}
