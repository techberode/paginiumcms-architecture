<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Translation\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\Translation\Exception\TranslationException;
use PaginiumCMS\Support\JsonHelper;

/**
 * Short-lived translation proposals bound to actor + resource + revision (It.76).
 */
final class TranslationProposalStore
{
    public const TTL_SECONDS = 86400;

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private string $directory = 'data/translation-proposals',
    ) {
    }

    /**
     * @param array<string, mixed> $proposal
     * @return array<string, mixed>
     */
    public function create(array $proposal): array
    {
        $id = bin2hex(random_bytes(16));
        $now = time();
        $proposal['id'] = $id;
        $proposal['createdAt'] = $now;
        $proposal['expiresAt'] = $now + self::TTL_SECONDS;
        $this->write($id, $proposal);

        return $proposal;
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $id, string $actorUserId): array
    {
        $proposal = $this->read($id);
        if ($proposal === null) {
            throw new TranslationException('Translation proposal not found', 404, 'NOT_FOUND');
        }
        if ((int) ($proposal['expiresAt'] ?? 0) < time()) {
            $this->delete($id);
            throw new TranslationException('Translation proposal expired', 410, 'EXPIRED');
        }
        $owner = (string) ($proposal['actorUserId'] ?? '');
        if ($owner === '' || !hash_equals($owner, $actorUserId)) {
            throw new TranslationException('Translation proposal not found', 404, 'NOT_FOUND');
        }

        return $proposal;
    }

    public function delete(string $id): void
    {
        $path = $this->path($id);
        if ($this->reader->exists($path)) {
            $this->writer->delete($path, false);
        }
    }

    public function purgeExpired(): void
    {
        try {
            $files = $this->reader->listFiles($this->directory, '*.json');
        } catch (\Throwable) {
            return;
        }

        $now = time();
        foreach ($files as $relative) {
            try {
                $decoded = JsonHelper::decode($this->reader->read($relative));
            } catch (\Throwable) {
                continue;
            }
            if ((int) ($decoded['expiresAt'] ?? 0) < $now) {
                $this->writer->delete($relative, false);
            }
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function read(string $id): ?array
    {
        $path = $this->path($id);
        if (!$this->reader->exists($path)) {
            return null;
        }

        try {
            $decoded = JsonHelper::decode($this->reader->read($path));
        } catch (\Throwable) {
            return null;
        }

        $out = [];
        foreach ($decoded as $key => $value) {
            if (is_string($key)) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $proposal
     */
    private function write(string $id, array $proposal): void
    {
        $this->writer->createDirectory($this->directory);
        $this->writer->write($this->path($id), JsonHelper::encode($proposal, JSON_PRETTY_PRINT), false);
    }

    private function path(string $id): string
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $id)) {
            throw new TranslationException('Invalid translation job id', 422, 'INVALID');
        }

        return $this->directory . '/' . $id . '.json';
    }
}
