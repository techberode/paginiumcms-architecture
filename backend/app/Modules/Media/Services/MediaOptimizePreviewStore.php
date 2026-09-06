<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Support\JsonHelper;

/**
 * Short-lived cache for media optimization previews (not written to media path until apply).
 */
final class MediaOptimizePreviewStore
{
    private const META_DIR = 'cache/media-optimize-previews';

    private const TTL_SECONDS = 900;

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
    ) {
    }

    /**
     * @param array<string, mixed> $stats
     */
    public function store(
        string $mediaPath,
        string $ownerUserId,
        string $mimeType,
        string $binary,
        array $stats,
    ): string {
        $token = bin2hex(random_bytes(16));
        $this->writer->writeBinary($this->binaryPath($token), $binary, true);
        $this->writer->writeBinary(
            $this->metaPath($token),
            JsonHelper::encode([
                'mediaPath' => $mediaPath,
                'ownerUserId' => $ownerUserId,
                'mimeType' => $mimeType,
                'stats' => $stats,
                'expiresAt' => time() + self::TTL_SECONDS,
            ]),
            true
        );

        return $token;
    }

    /**
     * @return array{
     *     mediaPath: string,
     *     ownerUserId: string,
     *     mimeType: string,
     *     binary: string,
     *     stats: array<string, mixed>
     * }|null
     */
    public function consume(string $token, string $mediaPath, string $ownerUserId): ?array
    {
        $meta = $this->readMeta($token);
        if ($meta === null) {
            return null;
        }

        if ($meta['mediaPath'] !== $mediaPath || $meta['ownerUserId'] !== $ownerUserId) {
            return null;
        }

        $binPath = $this->binaryPath($token);
        if (!$this->reader->exists($binPath)) {
            $this->delete($token);

            return null;
        }

        $result = [
            'mediaPath' => $meta['mediaPath'],
            'ownerUserId' => $meta['ownerUserId'],
            'mimeType' => $meta['mimeType'],
            'binary' => $this->reader->readBinary($binPath),
            'stats' => $meta['stats'],
        ];
        $this->delete($token);

        return $result;
    }

    /**
     * @return array{
     *     mediaPath: string,
     *     mimeType: string,
     *     binary: string,
     *     stats: array<string, mixed>
     * }|null
     */
    public function readForUser(string $token, string $ownerUserId): ?array
    {
        $meta = $this->readMeta($token);
        if ($meta === null || $meta['ownerUserId'] !== $ownerUserId) {
            return null;
        }

        $binPath = $this->binaryPath($token);
        if (!$this->reader->exists($binPath)) {
            return null;
        }

        return [
            'mediaPath' => $meta['mediaPath'],
            'mimeType' => $meta['mimeType'],
            'binary' => $this->reader->readBinary($binPath),
            'stats' => $meta['stats'],
        ];
    }

    /**
     * @return array{
     *     mediaPath: string,
     *     ownerUserId: string,
     *     mimeType: string,
     *     stats: array<string, mixed>,
     *     expiresAt: int
     * }|null
     */
    private function readMeta(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            return null;
        }

        $metaPath = $this->metaPath($token);
        if (!$this->reader->exists($metaPath)) {
            return null;
        }

        $decoded = json_decode($this->reader->read($metaPath), true);
        if (!is_array($decoded)) {
            $this->delete($token);

            return null;
        }

        $expiresAt = (int) ($decoded['expiresAt'] ?? 0);
        if ($expiresAt < time()) {
            $this->delete($token);

            return null;
        }

        $mediaPath = (string) ($decoded['mediaPath'] ?? '');
        $ownerUserId = (string) ($decoded['ownerUserId'] ?? '');
        $mimeType = (string) ($decoded['mimeType'] ?? '');
        $stats = $decoded['stats'] ?? [];
        if ($mediaPath === '' || $ownerUserId === '' || $mimeType === '' || !is_array($stats)) {
            $this->delete($token);

            return null;
        }

        return [
            'mediaPath' => $mediaPath,
            'ownerUserId' => $ownerUserId,
            'mimeType' => $mimeType,
            'stats' => $stats,
            'expiresAt' => $expiresAt,
        ];
    }

    private function delete(string $token): void
    {
        foreach ([$this->metaPath($token), $this->binaryPath($token)] as $path) {
            if ($this->reader->exists($path)) {
                $this->writer->delete($path, true);
            }
        }
    }

    private function metaPath(string $token): string
    {
        return self::META_DIR . '/' . $token . '.json';
    }

    private function binaryPath(string $token): string
    {
        return self::META_DIR . '/' . $token . '.img';
    }
}
