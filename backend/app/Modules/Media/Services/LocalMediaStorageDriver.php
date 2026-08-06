<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Modules\Media\Contracts\MediaStorageDriverInterface;

final class LocalMediaStorageDriver implements MediaStorageDriverInterface
{
    private const PROBE_PREFIX = 'media/.storage-probe-';

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
    ) {
    }

    public function put(string $relativePath, string $binary): void
    {
        $this->writer->writeBinary($relativePath, $binary, true);
    }

    public function read(string $relativePath): string
    {
        return $this->reader->readBinary($relativePath);
    }

    public function delete(string $relativePath): void
    {
        if ($this->reader->exists($relativePath)) {
            $this->writer->delete($relativePath, true);
        }
    }

    public function exists(string $relativePath): bool
    {
        return $this->reader->exists($relativePath);
    }

    public function checksum(string $relativePath): string
    {
        return hash('sha256', $this->read($relativePath));
    }

    public function publicUrl(string $relativePath): string
    {
        return '/storage/app/content/' . ltrim($relativePath, '/');
    }

    public function health(): array
    {
        $started = hrtime(true);
        $probePath = self::PROBE_PREFIX . bin2hex(random_bytes(4)) . '.bin';
        $payload = 'probe';

        try {
            $this->writer->writeBinary($probePath, $payload, true);
            $ok = $this->reader->exists($probePath)
                && $this->reader->readBinary($probePath) === $payload;
            if ($this->reader->exists($probePath)) {
                $this->writer->delete($probePath, true);
            }
        } catch (\Throwable) {
            $ok = false;
        }

        return [
            'ok' => $ok,
            'driver' => 'local',
            'latencyMs' => (int) ((hrtime(true) - $started) / 1_000_000),
            'message' => $ok
                ? 'Local media storage read/write/delete operational.'
                : 'Local media storage health probe failed.',
        ];
    }
}
