<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Logging\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Support\JsonHelper;

/**
 * Temporary P3 export artifacts (It.100e async log export).
 */
final class AdminExportArtifactStore
{
    private const INDEX = 'data/cache/admin-export-artifacts/index.json';

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private FileValidator $validator,
    ) {
    }

    /**
     * @return array{id: string, status: 'pending'}
     */
    public function createPending(string $userId, string $format, string $kind = 'application_logs'): array
    {
        $id = bin2hex(random_bytes(16));
        $now = gmdate('c');
        $entry = [
            'id' => $id,
            'kind' => $kind,
            'user_id' => $userId,
            'format' => $format,
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
            'filename' => null,
            'content_type' => null,
            'error' => null,
        ];

        $index = $this->loadIndex();
        $index[$id] = $entry;
        $this->persistIndex($index);

        return ['id' => $id, 'status' => 'pending'];
    }

    /**
     * @param array<string, mixed>|null $filters
     */
    public function markReady(string $id, string $binary, string $contentType, string $filename, ?array $filters = null): void
    {
        $path = $this->artifactPath($id);
        $this->writer->write($path, $binary, true);

        $index = $this->loadIndex();
        if (!array_key_exists($id, $index)) {
            return;
        }

        $index[$id]['status'] = 'ready';
        $index[$id]['updated_at'] = gmdate('c');
        $index[$id]['filename'] = $filename;
        $index[$id]['content_type'] = $contentType;
        $index[$id]['byte_size'] = strlen($binary);
        if ($filters !== null) {
            $index[$id]['filters'] = $filters;
        }
        $this->persistIndex($index);
    }

    public function markFailed(string $id, string $message): void
    {
        $index = $this->loadIndex();
        if (!array_key_exists($id, $index)) {
            return;
        }

        $index[$id]['status'] = 'failed';
        $index[$id]['updated_at'] = gmdate('c');
        $index[$id]['error'] = $message;
        $this->persistIndex($index);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForUser(string $id, string $userId): ?array
    {
        $entry = $this->loadIndex()[$id] ?? null;
        if (!is_array($entry) || (string) ($entry['user_id'] ?? '') !== $userId) {
            return null;
        }

        return $entry;
    }

    public function readBinary(string $id): ?string
    {
        $path = $this->artifactPath($id);
        if (!$this->reader->exists($path)) {
            return null;
        }

        return $this->reader->read($path);
    }

    private function artifactPath(string $id): string
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $id)) {
            throw new \InvalidArgumentException('Invalid export artifact id');
        }

        return 'data/cache/admin-export-artifacts/' . $id . '.bin';
    }

    /**
     * @return array<string, mixed>
     */
    private function loadIndex(): array
    {
        if (!$this->reader->exists(self::INDEX)) {
            return [];
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = JsonHelper::decode($this->reader->read(self::INDEX));
        } catch (\Throwable) {
            return [];
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $index
     */
    private function persistIndex(array $index): void
    {
        $now = time();
        foreach ($index as $id => $entry) {
            $created = isset($entry['created_at']) ? strtotime((string) $entry['created_at']) : false;
            if ($created !== false && $now - $created > 86400) {
                unset($index[$id]);
                $binPath = $this->artifactPath((string) $id);
                if ($this->reader->exists($binPath)) {
                    @unlink($this->validator->getAbsolutePath($binPath));
                }
            }
        }

        $this->writer->write(
            self::INDEX,
            JsonHelper::encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            true
        );
    }
}
