<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Performance;

use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Support\JsonHelper;

/**
 * Bounded ring buffer for in-request APM samples (Iteration 71).
 */
final class PerformanceSampleStore
{
    public const MAX_SAMPLES = 500;

    private const REGISTRY = 'data/metrics/apm-samples.json';

    public function __construct(
        private FileWriterInterface $writer,
        private string $absolutePath
    ) {
    }

    /**
     * @param array{
     *     ts: int,
     *     route: string,
     *     method: string,
     *     status: int,
     *     duration_ms: float,
     *     memory_delta_mb: float,
     *     storage_reads: int,
     *     storage_writes: int,
     *     cache_hits: int,
     *     cache_misses: int
     * } $sample
     */
    public function append(array $sample): void
    {
        $handle = fopen($this->absolutePath, 'c+');
        if ($handle === false) {
            return;
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return;
            }

            $samples = $this->readHandle($handle);
            $samples[] = $sample;
            if (count($samples) > self::MAX_SAMPLES) {
                $samples = array_slice($samples, -self::MAX_SAMPLES);
            }

            $this->writeHandle($handle, $samples);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function clear(): void
    {
        $this->writer->write(self::REGISTRY, '[]', true);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        if (!is_file($this->absolutePath)) {
            return [];
        }

        $raw = file_get_contents($this->absolutePath);
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        try {
            $decoded = JsonHelper::decode($raw);
        } catch (\Throwable) {
            return [];
        }

        $samples = [];
        foreach ($decoded as $row) {
            if (is_array($row)) {
                $samples[] = $row;
            }
        }

        return $samples;
    }

    /**
     * @param resource $handle
     * @return list<array<string, mixed>>
     */
    private function readHandle($handle): array
    {
        rewind($handle);
        $raw = stream_get_contents($handle);
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        try {
            $decoded = JsonHelper::decode($raw);
        } catch (\Throwable) {
            return [];
        }

        $samples = [];
        foreach ($decoded as $row) {
            if (is_array($row)) {
                $samples[] = $row;
            }
        }

        return $samples;
    }

    /**
     * @param resource $handle
     * @param list<array<string, mixed>> $samples
     */
    private function writeHandle($handle, array $samples): void
    {
        $payload = JsonHelper::encode($samples, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, $payload);
        fflush($handle);
    }
}
