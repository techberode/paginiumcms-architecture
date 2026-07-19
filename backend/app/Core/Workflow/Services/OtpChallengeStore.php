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
        $found = null;

        $this->withLockedStore(function (array $store) use ($id, &$found): array {
            $this->pruneExpired($store);
            $challenge = $store['challenges'][$id] ?? null;
            $found = is_array($challenge) ? $this->normalizeChallenge($challenge) : null;

            return $store;
        });

        return $found;
    }

    /**
     * @param array<string, mixed> $challenge
     * @return ChallengeRecord|null
     */
    private function normalizeChallenge(array $challenge): ?array
    {
        foreach (['id', 'flow', 'email', 'code_hash', 'expires_at', 'attempts', 'created_at'] as $key) {
            if (!isset($challenge[$key])) {
                return null;
            }
        }

        if (!isset($challenge['payload']) || !is_array($challenge['payload'])) {
            return null;
        }

        if (
            !is_string($challenge['id'])
            || !is_string($challenge['flow'])
            || !is_string($challenge['email'])
            || !is_string($challenge['code_hash'])
            || !is_int($challenge['expires_at'])
            || !is_int($challenge['attempts'])
            || !is_int($challenge['created_at'])
        ) {
            return null;
        }

        return [
            'id' => $challenge['id'],
            'flow' => $challenge['flow'],
            'email' => $challenge['email'],
            'code_hash' => $challenge['code_hash'],
            'payload' => $challenge['payload'],
            'expires_at' => $challenge['expires_at'],
            'attempts' => $challenge['attempts'],
            'created_at' => $challenge['created_at'],
        ];
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

            if (is_array($result) && isset($result['challenges'])) {
                $store = $result;
            }

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
