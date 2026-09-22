<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Models\MediaFile;
use PaginiumCMS\Core\Security\Services\UploadSecurityValidator;
use PaginiumCMS\Core\Security\Upload\UploadPolicyEngine;
use PaginiumCMS\Core\Security\Upload\UploadPolicyException;
use PaginiumCMS\Core\Security\Upload\UploadSurfaceRegistry;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface;
use PaginiumCMS\Modules\Media\Contracts\MediaStorageDriverInterface;
use PaginiumCMS\Modules\Media\MediaDocumentPolicy;
use PaginiumCMS\Modules\Media\MediaFormats;
use PaginiumCMS\Support\JsonHelper;
use PaginiumCMS\Support\Lang;
use RuntimeException;
use ZipArchive;

class MediaRepository implements MediaRepositoryInterface
{
    private const MEDIA_DIR = 'media';
    private const REGISTRY = 'media/registry.json';
    private const FOLDERS_INDEX = 'media/folders.json';
    private const FOLDER_MARKER = '.paginium-folder';
    private const BULK_DOWNLOAD_MAX_FILES = 50;
    private const BULK_DOWNLOAD_MAX_BYTES = 104_857_600;

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private SettingsRepositoryInterface $settings,
        private UploadSecurityValidator $uploadSecurity,
        private UploadPolicyEngine $uploadPolicy,
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
        string $folder = '',
        ?string $userId = null
    ): MediaFile {
        $binary = is_resource($contents) ? stream_get_contents($contents) : $contents;
        if (!is_string($binary) || $binary === '') {
            throw new FlatFileException('Prázdny alebo neplatný súbor');
        }

        $declaredMime = MediaFormats::coalesceDeclaredMime($originalName, $mimeType);

        if ($this->uploadPolicy->isUnifiedEnabled()) {
            try {
                $mimeType = $this->uploadPolicy->enforceBinary(
                    $this->resolveMediaUploadSurface($originalName, $declaredMime),
                    $originalName,
                    $binary,
                    $declaredMime,
                    $userId
                );
            } catch (UploadPolicyException $exception) {
                throw new FlatFileException($exception->getMessage(), 0, $exception);
            }
        } else {
            $this->uploadSecurity->assertMediaUploadFilenameAllowed($originalName, $declaredMime);

            $allowedMimeTypes = $this->uploadSecurity->resolveAllowedMimeTypes($this->resolveMediaMimeTypes());
            $mimeType = MediaFormats::validate(
                $originalName,
                $binary,
                $declaredMime,
                $allowedMimeTypes,
                $this->uploadSecurity->shouldScanMagicBytes()
            );
        }

        $folder = $this->normalizeFolder($folder);
        $safeName = $this->sanitizeFileName($originalName);
        $media = new MediaFile();

        $prefix = self::MEDIA_DIR . ($folder !== '' ? '/' . $folder : '');
        $relativePath = $prefix . '/' . $media->getId() . '_' . $safeName;

        if (!$this->uploadPolicy->isUnifiedEnabled()) {
            $maxBytes = $this->uploadSecurity->resolveMaxUploadBytes(
                MediaFormats::isVideoMime($mimeType)
                    ? $this->resolveMediaMaxVideoUploadBytes()
                    : $this->resolveMediaMaxUploadBytes()
            );
            if (strlen($binary) > $maxBytes) {
                throw new FlatFileException('Súbor presahuje maximálnu povolenú veľkosť');
            }
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

    public function createFolder(string $folder): string
    {
        $folder = $this->normalizeFolder($folder);
        if ($folder === '') {
            throw new FlatFileException(Lang::get('folder_invalid', [], 'media'));
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

        return $folder;
    }

    public function deleteFolder(string $folder, bool $recursive = false): int
    {
        $folder = $this->normalizeFolder($folder);
        if ($folder === '') {
            throw new FlatFileException(Lang::get('folder_invalid', [], 'media'));
        }

        if (!$recursive && $this->folderHasChildren($folder)) {
            throw new FlatFileException(Lang::get('folder_not_empty', [], 'media'));
        }

        $deleted = 0;
        foreach ($this->listFilesInFolderTree($folder) as $file) {
            $this->delete($file->getPath());
            ++$deleted;
        }

        $this->removeFolderTreeFromIndexAndMarkers($folder);

        return $deleted;
    }

    public function moveFolder(string $from, string $to): string
    {
        $from = $this->normalizeFolder($from);
        $to = $this->normalizeFolder($to);

        if ($from === '') {
            throw new FlatFileException(Lang::get('folder_invalid', [], 'media'));
        }

        if ($from === $to) {
            return $from;
        }

        if ($to !== '' && str_starts_with($to, $from . '/')) {
            throw new FlatFileException(Lang::get('folder_move_into_self', [], 'media'));
        }

        if ($this->folderIndexed($to) && !str_starts_with($to, $from . '/')) {
            throw new FlatFileException(Lang::get('folder_exists', [], 'media'));
        }

        $parent = str_contains($to, '/')
            ? substr($to, 0, (int) strrpos($to, '/'))
            : '';
        if ($parent !== '') {
            $this->createFolder($parent);
        }

        foreach ($this->listFilesInFolderTree($from) as $file) {
            $newFolder = $this->remapFolderPrefix($file->getFolder(), $from, $to);
            $this->relocateMediaFile($file, $newFolder);
        }

        $this->moveFolderMarkers($from, $to);
        $this->remapFolderIndex($from, $to);
        $this->createFolder($to);

        return $to;
    }

    public function copyFolder(string $from, string $to): string
    {
        $from = $this->normalizeFolder($from);
        $to = $this->normalizeFolder($to);

        if ($from === '') {
            throw new FlatFileException(Lang::get('folder_invalid', [], 'media'));
        }

        if ($from === $to) {
            throw new FlatFileException(Lang::get('folder_exists', [], 'media'));
        }

        if ($to !== '' && str_starts_with($to, $from . '/')) {
            throw new FlatFileException(Lang::get('folder_move_into_self', [], 'media'));
        }

        if ($this->folderIndexed($to)) {
            throw new FlatFileException(Lang::get('folder_exists', [], 'media'));
        }

        $this->createFolder($to);

        foreach ($this->listFilesInFolderTree($from) as $file) {
            $newFolder = $this->remapFolderPrefix($file->getFolder(), $from, $to);
            $binary = $this->readBinary($file->getPath());
            $copy = $this->saveUpload(
                $file->getFileName(),
                $binary,
                $file->getMimeType(),
                $file->getAltText(),
                $newFolder
            );
            $title = $file->getTitle();
            if ($title !== '') {
                $copy->setTitle($title);
                $this->update($copy);
            }
        }

        $nestedFolders = array_values(array_filter(
            $this->loadFolderIndex(),
            static fn (string $indexed): bool => $indexed !== $from && str_starts_with($indexed, $from . '/')
        ));
        foreach ($nestedFolders as $indexed) {
            $this->createFolder($this->remapFolderPrefix($indexed, $from, $to));
        }

        return $to;
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

        if (isset($filters['type']) && $filters['type'] === 'video' && !MediaFormats::isVideoMime($file->getMimeType())) {
            return false;
        }

        if (isset($filters['type']) && $filters['type'] === 'document' && !MediaFormats::isDocumentMime($file->getMimeType())) {
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
        $media = $this->settings->group('media');

        $documentMimeTypes = MediaDocumentPolicy::isEnabled($this->settings)
            ? MediaDocumentPolicy::allowedMimeTypes($this->settings)
            : [];

        return array_merge(
            MediaFormats::toApiPayload($this->resolveAllowedMimeTypes()),
            [
                'imageOptimization' => MediaImageOptimizer::capabilities(),
                'maxVideoUploadSizeKb' => max(1024, (int) ($media['maxVideoUploadSizeKb'] ?? 102400)),
                'documentsEnabled' => MediaDocumentPolicy::isEnabled($this->settings),
                'documentMimeTypes' => $documentMimeTypes,
                'documentAccept' => MediaFormats::buildAcceptHeader($documentMimeTypes),
                'maxDocumentUploadSizeKb' => max(64, (int) ($media['maxDocumentUploadSizeKb'] ?? 20480)),
                'textEditableMimeTypes' => array_values(array_filter(
                    $documentMimeTypes,
                    static fn (string $mime): bool => MediaFormats::isTextEditableMime($mime)
                )),
                'adminPdfPreviewMimeTypes' => array_values(array_filter(
                    $documentMimeTypes,
                    static fn (string $mime): bool => MediaFormats::isAdminPdfPreviewMime($mime)
                )),
            ]
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

        if ($types === []) {
            $types = MediaFormats::defaultMimeTypes();
        }

        if (MediaDocumentPolicy::isEnabled($this->settings)) {
            $types = array_values(array_unique(array_merge(
                $types,
                MediaDocumentPolicy::allowedMimeTypes($this->settings)
            )));
        }

        return array_values(array_filter(
            $types,
            static fn (string $mime): bool => MediaFormats::isKnownMime($mime)
        ));
    }

    private function resolveMediaMaxUploadBytes(): int
    {
        $maxKb = (int) ($this->settings->group('media')['maxUploadSizeKb'] ?? 5120);

        return max(64, $maxKb) * 1024;
    }

    private function resolveMediaMaxVideoUploadBytes(): int
    {
        $maxKb = (int) ($this->settings->group('media')['maxVideoUploadSizeKb'] ?? 102400);

        return max(1024, min(524288, $maxKb)) * 1024;
    }

    private function resolveMediaUploadSurface(string $originalName, string $declaredMime): string
    {
        if (MediaFormats::isVideoMime($declaredMime)) {
            return UploadSurfaceRegistry::SURFACE_MEDIA_VIDEO_UPLOAD;
        }

        if (MediaFormats::isDocumentMime($declaredMime)) {
            return UploadSurfaceRegistry::SURFACE_MEDIA_DOCUMENT_UPLOAD;
        }

        $inferred = MediaFormats::guessMimeFromExtension($originalName);
        if ($inferred !== null) {
            if (MediaFormats::isVideoMime($inferred)) {
                return UploadSurfaceRegistry::SURFACE_MEDIA_VIDEO_UPLOAD;
            }

            if (MediaFormats::isDocumentMime($inferred)) {
                return UploadSurfaceRegistry::SURFACE_MEDIA_DOCUMENT_UPLOAD;
            }
        }

        return UploadSurfaceRegistry::SURFACE_MEDIA_UPLOAD;
    }

    /**
     * @return array{content: string, version: int, mimeType: string, path: string}
     */
    public function readTextContent(string $path): array
    {
        $media = $this->findByPath($path);
        if ($media === null) {
            throw new FlatFileException('Médium nebolo nájdené');
        }

        if (!MediaFormats::isTextEditableMime($media->getMimeType())) {
            throw new FlatFileException('Súbor nie je editovateľný text');
        }

        $binary = $this->readBinary($path);
        if (strlen($binary) > 2_097_152) {
            throw new FlatFileException('Textový súbor presahuje limit 2 MB pre editáciu');
        }

        return [
            'path' => $path,
            'mimeType' => $media->getMimeType(),
            'content' => $binary,
            'version' => $this->readContentVersion($path),
        ];
    }

    /**
     * @return array{media: array<string, mixed>, version: int}
     */
    public function saveTextContent(string $path, string $content, ?int $expectedVersion): array
    {
        $media = $this->findByPath($path);
        if ($media === null) {
            throw new FlatFileException('Médium nebolo nájdené');
        }

        if (!MediaFormats::isTextEditableMime($media->getMimeType())) {
            throw new FlatFileException('Súbor nie je editovateľný text');
        }

        if (str_contains($content, "\0")) {
            throw new FlatFileException('Text obsahuje neplatné znaky');
        }

        if (strlen($content) > 2_097_152) {
            throw new FlatFileException('Text presahuje limit 2 MB');
        }

        if (!MediaFormats::contentMatchesMime($content, $media->getMimeType())) {
            throw new FlatFileException('Text neprešiel bezpečnostnou kontrolou');
        }

        $currentVersion = $this->readContentVersion($path);
        if ($expectedVersion !== null && $expectedVersion !== $currentVersion) {
            throw new FlatFileException('Súbor bol medzitým upravený — obnovte obsah a skúste znova');
        }

        $this->storage()->put($path, $content);
        $sizeBytes = strlen($content);
        $media->setSizeBytes($sizeBytes);
        $nextVersion = $currentVersion + 1;
        $this->writeSidecarWithContentVersion($media, $nextVersion);

        $registry = $this->loadRegistry();
        foreach ($registry as $index => $entry) {
            if (($entry['path'] ?? '') === $path) {
                $registry[$index]['sizeBytes'] = strlen($content);
                break;
            }
        }
        $this->saveRegistry($registry);

        return [
            'media' => $media->jsonSerialize(),
            'version' => $nextVersion,
        ];
    }

    /**
     * @param list<string> $paths
     */
    public function buildBulkDownloadArchive(array $paths): string
    {
        if ($paths === []) {
            throw new FlatFileException(Lang::get('paths_required', [], 'media'));
        }

        if (count($paths) > self::BULK_DOWNLOAD_MAX_FILES) {
            throw new FlatFileException(Lang::get('bulk_download_too_many', [], 'media'));
        }

        if (!class_exists(ZipArchive::class)) {
            throw new FlatFileException(Lang::get('bulk_download_zip_unavailable', [], 'media'));
        }

        $totalBytes = 0;
        $entries = [];
        $usedNames = [];

        foreach ($paths as $path) {
            if ($path === '') {
                continue;
            }

            MediaStoragePathGuard::assertSafeRelativePath($path);
            $media = $this->findByPath($path);
            if ($media === null) {
                throw new FlatFileException(Lang::get('not_found', [], 'media'));
            }

            $sizeBytes = $media->getSizeBytes();
            $totalBytes += $sizeBytes;
            if ($totalBytes > self::BULK_DOWNLOAD_MAX_BYTES) {
                throw new FlatFileException(Lang::get('bulk_download_too_large', [], 'media'));
            }

            $zipName = $this->uniqueZipEntryName($media->getFileName(), $usedNames);
            $entries[] = ['path' => $path, 'zipName' => $zipName];
        }

        if ($entries === []) {
            throw new FlatFileException(Lang::get('paths_required', [], 'media'));
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'pag_media_zip_');
        if ($tempPath === false) {
            throw new RuntimeException('Could not create temporary ZIP file');
        }

        $zip = new ZipArchive();
        if ($zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($tempPath);

            throw new FlatFileException(Lang::get('bulk_download_zip_unavailable', [], 'media'));
        }

        foreach ($entries as $entry) {
            try {
                $binary = $this->readBinary($entry['path']);
            } catch (FlatFileException) {
                $zip->close();
                @unlink($tempPath);

                throw new FlatFileException(Lang::get('not_found', [], 'media'));
            }

            if ($zip->addFromString($entry['zipName'], $binary) !== true) {
                $zip->close();
                @unlink($tempPath);

                throw new FlatFileException(Lang::get('bulk_download_zip_unavailable', [], 'media'));
            }
        }

        $zip->close();

        return $tempPath;
    }

    /**
     * @param array<string, true> $usedNames
     */
    private function uniqueZipEntryName(string $fileName, array &$usedNames): string
    {
        $base = basename(str_replace('\\', '/', $fileName));
        if ($base === '' || $base === '.' || $base === '..') {
            $base = 'file';
        }

        $candidate = $base;
        $counter = 1;
        while (isset($usedNames[$candidate])) {
            $dot = strrpos($base, '.');
            if ($dot !== false && $dot > 0) {
                $candidate = substr($base, 0, $dot) . '-' . $counter . substr($base, $dot);
            } else {
                $candidate = $base . '-' . $counter;
            }
            ++$counter;
        }

        $usedNames[$candidate] = true;

        return $candidate;
    }

    private function readContentVersion(string $path): int
    {
        $sidecar = $this->sidecarPath($path);
        if (!$this->reader->exists($sidecar)) {
            return 1;
        }

        try {
            $data = json_decode($this->reader->read($sidecar), true);

            return max(1, (int) ($data['contentVersion'] ?? 1));
        } catch (FlatFileException) {
            return 1;
        }
    }

    private function writeSidecarWithContentVersion(MediaFile $file, int $contentVersion): void
    {
        $payload = [
            'altText' => $file->getAltText(),
            'title' => $file->getTitle(),
            'folder' => $file->getFolder(),
            'updatedAt' => time(),
            'contentVersion' => $contentVersion,
        ];

        $json = JsonHelper::encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $this->writer->write($this->sidecarPath($file->getPath()), $json, true);
    }

    private function normalizeFolder(string $folder): string
    {
        $folder = trim(str_replace('\\', '/', $folder), '/');
        if ($folder === '' || str_contains($folder, '..')) {
            return '';
        }

        $segments = [];
        foreach (explode('/', $folder) as $segment) {
            $slug = $this->slugifyFolderSegment($segment);
            if ($slug === '') {
                throw new FlatFileException(Lang::get('folder_invalid', [], 'media'));
            }

            if (!preg_match('#^[\p{L}\p{N}][\p{L}\p{N}_-]*$#u', $slug)) {
                throw new FlatFileException(Lang::get('folder_invalid', [], 'media'));
            }

            $segments[] = $slug;
        }

        return implode('/', $segments);
    }

    private function slugifyFolderSegment(string $segment): string
    {
        $segment = trim($segment);
        if ($segment === '') {
            return '';
        }

        $segment = preg_replace('/[\s_]+/u', '-', $segment) ?? $segment;
        $segment = preg_replace('/-+/u', '-', $segment) ?? $segment;

        return trim($segment, '-');
    }

    /**
     * @return list<MediaFile>
     */
    private function listFilesInFolderTree(string $folder): array
    {
        $files = [];
        foreach ($this->findAll() as $file) {
            $fileFolder = $file->getFolder();
            if ($fileFolder === $folder || ($folder !== '' && str_starts_with($fileFolder, $folder . '/'))) {
                $files[] = $file;
            }
        }

        return $files;
    }

    private function folderHasChildren(string $folder): bool
    {
        if ($this->listFilesInFolderTree($folder) !== []) {
            return true;
        }

        foreach ($this->loadFolderIndex() as $indexed) {
            if ($indexed !== $folder && str_starts_with($indexed, $folder . '/')) {
                return true;
            }
        }

        return false;
    }

    private function folderIndexed(string $folder): bool
    {
        if ($folder === '') {
            return false;
        }

        return in_array($folder, $this->loadFolderIndex(), true);
    }

    private function removeFolderTreeFromIndexAndMarkers(string $folder): void
    {
        $remaining = [];
        foreach ($this->loadFolderIndex() as $indexed) {
            if ($indexed === $folder || ($folder !== '' && str_starts_with($indexed, $folder . '/'))) {
                $marker = self::MEDIA_DIR . '/' . $indexed . '/' . self::FOLDER_MARKER;
                if ($this->reader->exists($marker)) {
                    $this->writer->delete($marker, true);
                }

                continue;
            }

            $remaining[] = $indexed;
        }

        sort($remaining);
        $this->saveFolderIndex($remaining);
    }

    private function remapFolderPrefix(string $path, string $from, string $to): string
    {
        if ($path === $from) {
            return $to;
        }

        if ($from !== '' && str_starts_with($path, $from . '/')) {
            $suffix = substr($path, strlen($from) + 1);

            return $to === '' ? $suffix : $to . '/' . $suffix;
        }

        return $path;
    }

    private function relocateMediaFile(MediaFile $file, string $newFolder): void
    {
        $newFolder = $this->normalizeFolder($newFolder);
        $oldPath = $file->getPath();
        $basename = basename($oldPath);
        $newPath = self::MEDIA_DIR . ($newFolder !== '' ? '/' . $newFolder : '') . '/' . $basename;

        if ($oldPath === $newPath) {
            if ($file->getFolder() !== $newFolder) {
                $file->setFolder($newFolder);
                $this->update($file);
            }

            return;
        }

        $storage = $this->storage();
        $binary = $storage->read($oldPath);
        $storage->put($newPath, $binary);
        $storage->delete($oldPath);

        $oldSidecar = $this->sidecarPath($oldPath);
        $newSidecar = $this->sidecarPath($newPath);
        if ($this->reader->exists($oldSidecar)) {
            try {
                $payload = json_decode($this->reader->read($oldSidecar), true);
                if (is_array($payload)) {
                    $payload['folder'] = $newFolder;
                    $payload['updatedAt'] = time();
                    $json = JsonHelper::encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    $this->writer->write($newSidecar, $json, true);
                }
            } catch (FlatFileException) {
                // Sidecar is optional metadata.
            }

            try {
                $this->writer->delete($oldSidecar, true);
            } catch (FlatFileException) {
                // Sidecar may already be gone.
            }
        }

        $file->setPath($newPath);
        $file->setFolder($newFolder);
        $file->setUrl($storage->publicUrl($newPath));
        $this->replaceRegistryEntry($oldPath, $file);
    }

    private function replaceRegistryEntry(string $previousPath, MediaFile $file): void
    {
        $registry = $this->loadRegistry();
        $updated = false;

        foreach ($registry as $index => $entry) {
            if (($entry['path'] ?? '') !== $previousPath) {
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
    }

    private function moveFolderMarkers(string $from, string $to): void
    {
        $paths = [];
        foreach ($this->loadFolderIndex() as $indexed) {
            if ($indexed === $from || ($from !== '' && str_starts_with($indexed, $from . '/'))) {
                $paths[] = $indexed;
            }
        }

        usort($paths, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        foreach ($paths as $oldPath) {
            $newPath = $this->remapFolderPrefix($oldPath, $from, $to);
            if ($newPath === '') {
                continue;
            }

            $oldMarker = self::MEDIA_DIR . '/' . $oldPath . '/' . self::FOLDER_MARKER;
            $newMarker = self::MEDIA_DIR . '/' . $newPath . '/' . self::FOLDER_MARKER;
            if (!$this->reader->exists($oldMarker)) {
                continue;
            }

            try {
                $this->writer->move($oldMarker, $newMarker);
            } catch (FlatFileException) {
                $payload = $this->reader->read($oldMarker);
                $this->writer->write($newMarker, $payload, true);
                $this->writer->delete($oldMarker, true);
            }
        }
    }

    private function remapFolderIndex(string $from, string $to): void
    {
        $updated = [];
        foreach ($this->loadFolderIndex() as $indexed) {
            if ($indexed === $from || ($from !== '' && str_starts_with($indexed, $from . '/'))) {
                $newPath = $this->remapFolderPrefix($indexed, $from, $to);
                if ($newPath !== '') {
                    $updated[] = $newPath;
                }

                continue;
            }

            $updated[] = $indexed;
        }

        sort($updated);
        $this->saveFolderIndex(array_values(array_unique($updated)));
    }

    private function storage(): MediaStorageDriverInterface
    {
        return $this->storageFactory->create(null, true, $this->settings->group('media'));
    }
}
