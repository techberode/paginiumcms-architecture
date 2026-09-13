<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Services;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Modules\Media\Contracts\MediaStorageDriverInterface;

final class S3MediaStorageDriver implements MediaStorageDriverInterface
{
    private const PROBE_PREFIX = '.storage-probe-';

    public function __construct(
        private FilesystemOperator $filesystem,
        private S3MediaStorageConfig $config,
        private MediaUrlResolver $urlResolver,
    ) {
    }

    public function put(string $relativePath, string $binary): void
    {
        $key = MediaStoragePathGuard::assertSafeRelativePath($relativePath);

        try {
            $this->filesystem->write($key, $binary, $this->visibilityOptions());
        } catch (FilesystemException $exception) {
            throw new FlatFileException('S3 media upload failed: ' . $exception->getMessage(), 0, $exception);
        }
    }

    public function read(string $relativePath): string
    {
        $key = MediaStoragePathGuard::assertSafeRelativePath($relativePath);

        try {
            if (!$this->filesystem->fileExists($key)) {
                throw new FlatFileException('Media object not found');
            }

            return $this->filesystem->read($key);
        } catch (FilesystemException $exception) {
            throw new FlatFileException('S3 media read failed: ' . $exception->getMessage(), 0, $exception);
        }
    }

    public function delete(string $relativePath): void
    {
        $key = MediaStoragePathGuard::assertSafeRelativePath($relativePath);

        try {
            if ($this->filesystem->fileExists($key)) {
                $this->filesystem->delete($key);
            }
        } catch (FilesystemException $exception) {
            throw new FlatFileException('S3 media delete failed: ' . $exception->getMessage(), 0, $exception);
        }
    }

    public function exists(string $relativePath): bool
    {
        $key = MediaStoragePathGuard::assertSafeRelativePath($relativePath);

        try {
            return $this->filesystem->fileExists($key);
        } catch (FilesystemException) {
            return false;
        }
    }

    public function checksum(string $relativePath): string
    {
        return hash('sha256', $this->read($relativePath));
    }

    public function publicUrl(string $relativePath): string
    {
        MediaStoragePathGuard::assertSafeRelativePath($relativePath);

        return $this->urlResolver->s3PublicUrl($relativePath, $this->config);
    }

    public function health(): array
    {
        $started = hrtime(true);
        $probePath = self::PROBE_PREFIX . bin2hex(random_bytes(4)) . '.bin';
        $payload = 'probe';

        try {
            $this->put($probePath, $payload);
            $ok = $this->exists($probePath) && $this->read($probePath) === $payload;
            if ($this->exists($probePath)) {
                $this->delete($probePath);
            }
        } catch (\Throwable) {
            $ok = false;
        }

        return [
            'ok' => $ok,
            'driver' => 's3',
            'latencyMs' => (int) ((hrtime(true) - $started) / 1_000_000),
            'message' => $ok
                ? 'S3-compatible media storage read/write/delete operational.'
                : 'S3-compatible media storage health probe failed.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function visibilityOptions(): array
    {
        return [
            'visibility' => $this->config->visibility === 'public' ? 'public' : 'private',
        ];
    }
}
