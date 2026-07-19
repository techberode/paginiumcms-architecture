<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Workflow\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Flat-file store for pending OTP challenges (Iteration 41).
 *
 * @phpstan-type ChallengeRecord array{
 *     id: string,
 *     flow: string,
 *     email: string,
 *     code_hash: string,
 *     payload: array<string, mixed>,
 *     expires_at: int,
 *     attempts: int,
 *     created_at: int
 * }
 */
final class OtpChallengeStore
{
    private string $absolutePath;

    public function __construct(
        private FileReaderInterface $reader,
        private string $storeFile = 'data/otp-challenges.json'
    ) {
        $this->absolutePath = rtrim($this->reader->getBasePath(), '/') . '/' . ltrim($this->storeFile, '/');
    }

    /**
     * @param ChallengeRecord $record
     */
    public function save(array $record): void
    {
        $this->withLockedStore(function (array $store) use ($record): array {
            $challenges = is_array($store['challenges'] ?? null) ? $store['challenges'] : [];
            $challenges[$record['id']] = $record;
            $store['challenges'] = $challenges;

            return $store;
        });
    }

    /**
     * @return ChallengeRecord|null
     */
    public function find(string $id): ?array
    {
        return $this->withLockedStore(function (array $store) use ($id): ?array {
            $this->pruneExpired($store);
            $challenge = $store['challenges'][$id] ?? null;

            return is_array($challenge) ? $challenge : null;
        });
    }

    public function delete(string $id): void
    {
        $this->withLockedStore(function (array $store) use ($id): array {
            unset($store['challenges'][$id]);

            return $store;
        });
    }

    /**
     * @param callable(array<string, mixed>): (array<string, mixed>|null) $callback
     */
    private function withLockedStore(callable $callback): mixed
    {
        $dir = dirname($this->absolutePath);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Cannot create OTP store directory: ' . $dir);
        }

        $handle = fopen($this->absolutePath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Cannot open OTP store: ' . $this->absolutePath);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Cannot lock OTP store');
            }

            $raw = stream_get_contents($handle);
            $store = is_string($raw) && $raw !== ''
                ? (json_decode($raw, true) ?: [])
                : ['challenges' => []];

            if (!is_array($store)) {
                $store = ['challenges' => []];
            }

            $result = $callback($store);

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, JsonHelper::encode($store, JSON_UNESCAPED_UNICODE));
            fflush($handle);
            flock($handle, LOCK_UN);

            return $result;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param array<string, mixed> $store
     */
    private function pruneExpired(array &$store): void
    {
        $now = time();
        $challenges = is_array($store['challenges'] ?? null) ? $store['challenges'] : [];

        foreach ($challenges as $id => $challenge) {
            if (!is_array($challenge) || (int) ($challenge['expires_at'] ?? 0) < $now) {
                unset($challenges[$id]);
            }
        }

        $store['challenges'] = $challenges;
    }
}
