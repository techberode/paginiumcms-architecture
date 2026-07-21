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
use PaginiumCMS\Modules\Media\MediaFormats;
use PaginiumCMS\Support\JsonHelper;

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
        private UploadSecurityValidator $uploadSecurity
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

        $this->writer->writeBinary($relativePath, $binary, true);

        $media->setPath($relativePath);
        $media->setFileName($safeName);
        $media->setUrl('/storage/app/content/' . $relativePath);
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

            if ($this->reader->exists($path)) {
                $this->writer->delete($path, true);
            }

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
    public function formatsPayload(): array
    {
        return MediaFormats::toApiPayload($this->resolveAllowedMimeTypes());
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
}
