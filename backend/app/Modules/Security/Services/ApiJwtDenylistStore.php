<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Flat-file deny-list for revoked JWT `jti` values (It.74 Phase 74b).
 */
final class ApiJwtDenylistStore
{
    private string $absolutePath;

    public function __construct(
        private FileReaderInterface $reader,
        private string $storeFile = 'data/api-jwt-denylist.json',
    ) {
        $this->absolutePath = rtrim($this->reader->getBasePath(), '/') . '/' . ltrim($this->storeFile, '/');
    }

    public function isDenied(string $jti): bool
    {
        $store = $this->readStore();
        $entries = is_array($store['entries'] ?? null) ? $store['entries'] : [];

        return isset($entries[$jti]);
    }

    public function deny(string $jti, int $expiresAt): void
    {
        $this->withLockedStore(function (array $store) use ($jti, $expiresAt): array {
            if (!isset($store['entries']) || !is_array($store['entries'])) {
                $store['entries'] = [];
            }

            $store['entries'][$jti] = [
                'jti' => $jti,
                'exp' => $expiresAt,
                'deniedAt' => gmdate('c'),
            ];

            $now = time();
            foreach ($store['entries'] as $id => $entry) {
                if (!is_array($entry)) {
                    unset($store['entries'][$id]);
                    continue;
                }

                $exp = (int) ($entry['exp'] ?? 0);
                if ($exp > 0 && $exp < $now) {
                    unset($store['entries'][$id]);
                }
            }

            return $store;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function readStore(): array
    {
        if (!is_file($this->absolutePath)) {
            return ['schemaVersion' => 1, 'entries' => []];
        }

        $raw = file_get_contents($this->absolutePath);
        $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;

        return is_array($decoded) ? $decoded : ['schemaVersion' => 1, 'entries' => []];
    }

    /**
     * @param callable(array<string, mixed>): array<string, mixed> $callback
     */
    private function withLockedStore(callable $callback): void
    {
        $dir = dirname($this->absolutePath);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Cannot create JWT deny-list directory: ' . $dir);
        }

        $handle = fopen($this->absolutePath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Cannot open JWT deny-list store: ' . $this->absolutePath);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Cannot lock JWT deny-list store');
            }

            $raw = stream_get_contents($handle);
            $store = is_string($raw) && $raw !== ''
                ? (json_decode($raw, true) ?: [])
                : ['schemaVersion' => 1, 'entries' => []];

            if (!is_array($store)) {
                $store = ['schemaVersion' => 1, 'entries' => []];
            }

            $store = $callback($store);

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, JsonHelper::encode($store, JSON_UNESCAPED_UNICODE));
            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }
}
