<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Models\MediaFile;
use PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface;
use PaginiumCMS\Support\JsonHelper;

class MediaRepository implements MediaRepositoryInterface
{
    private const MEDIA_DIR = 'media';
    private const REGISTRY = 'media/registry.json';

    /** @var array<int, string> */
    private array $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'application/pdf',
    ];

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer
    ) {
    }

    /**
     * @param array<int|string, mixed> $filters
     * @return array<int|string, mixed>
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

    public function saveUpload(string $originalName, $contents, string $mimeType, string $altText = ''): MediaFile
    {
        if (!in_array($mimeType, $this->allowedMimeTypes, true)) {
            throw new FlatFileException('Nepodporovaný typ súboru: ' . $mimeType);
        }

        $safeName = $this->sanitizeFileName($originalName);
        $media = new MediaFile();
        $relativePath = self::MEDIA_DIR . '/' . $media->getId() . '_' . $safeName;

        $binary = is_resource($contents) ? stream_get_contents($contents) : $contents;
        if (!is_string($binary) || $binary === '') {
            throw new FlatFileException('Prázdny alebo neplatný súbor');
        }

        $this->writer->write($relativePath, $binary, true);

        $media->setPath($relativePath);
        $media->setFileName($safeName);
        $media->setUrl('/storage/app/content/' . $relativePath);
        $media->setSizeBytes(strlen($binary));
        $media->setMimeType($mimeType);
        $media->setAltText($altText);

        $registry = $this->loadRegistry();
        $registry[] = $media->jsonSerialize();
        $this->saveRegistry($registry);

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

            unset($registry[$index]);
            $found = true;
            break;
        }

        if (!$found) {
            throw new FlatFileException('Médium nebolo nájdené');
        }

        $this->saveRegistry(array_values($registry));
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

        foreach (['id', 'path', 'fileName', 'url', 'sizeBytes', 'mimeType', 'uploadedAt', 'altText'] as $property) {
            if (!array_key_exists($property, $entry)) {
                continue;
            }

            $prop = $reflection->getProperty($property);
            $prop->setValue($file, $entry[$property]);
        }

        return $file;
    }

    /**
     * @param array<int|string, mixed> $filters
     */
    private function matchesFilters(MediaFile $file, array $filters): bool
    {
        if (empty($filters)) {
            return true;
        }

        if (isset($filters['mimeType']) && $file->getMimeType() !== $filters['mimeType']) {
            return false;
        }

        if (isset($filters['type']) && $filters['type'] === 'image' && !$file->isImage()) {
            return false;
        }

        return true;
    }

    private function sanitizeFileName(string $name): string
    {
        $name = basename($name);
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '-', $name) ?? 'upload.bin';

        return $name !== '' ? $name : 'upload.bin';
    }
}
