<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Flat-file tracker for failed login attempts (Iteration 22).
 *
 * Storage: `data/security/login_attempts.json` with flock(LOCK_EX).
 */
final class LoginAttemptTracker
{
    private string $absolutePath;

    public function __construct(
        private FileReaderInterface $reader,
        private SettingsRepositoryInterface $settings,
        private string $storeFile = 'data/security/login_attempts.json'
    ) {
        $this->absolutePath = rtrim($this->reader->getBasePath(), '/') . '/' . ltrim($this->storeFile, '/');
    }

    /**
     * @return array{locked: bool, retryAfter: int, reason: string|null}
     */
    public function status(string $ip, string $email): array
    {
        $security = $this->settings->group('security');
        $maxAttempts = max(1, (int) ($security['maxLoginAttempts'] ?? 5));
        $lockoutMinutes = max(1, (int) ($security['lockoutMinutes'] ?? 15));
        $windowSeconds = $lockoutMinutes * 60;
        $now = time();

        return $this->withLockedStore(function (array $store) use ($ip, $email, $maxAttempts, $windowSeconds, $now): array {
            $this->pruneExpired($store, $now, $windowSeconds);

            foreach ($this->keysFor($ip, $email) as $key) {
                $attempts = $store['attempts'][$key] ?? [];
                if (!is_array($attempts)) {
                    continue;
                }

                $recent = array_values(array_filter(
                    $attempts,
                    static fn (mixed $ts): bool => is_int($ts) && $ts > ($now - $windowSeconds)
                ));

                if (count($recent) >= $maxAttempts) {
                    $oldest = min($recent);
                    $retryAfter = max(1, ($oldest + $windowSeconds) - $now);

                    return [
                        'locked' => true,
                        'retryAfter' => $retryAfter,
                        'reason' => $key === 'ip:' . $ip ? 'ip' : 'email',
                    ];
                }
            }

            return ['locked' => false, 'retryAfter' => 0, 'reason' => null];
        });
    }

    public function isLocked(string $ip, string $email): bool
    {
        return $this->status($ip, $email)['locked'];
    }

    public function recordFailure(string $ip, string $email): bool
    {
        $security = $this->settings->group('security');
        $maxAttempts = max(1, (int) ($security['maxLoginAttempts'] ?? 5));
        $lockoutMinutes = max(1, (int) ($security['lockoutMinutes'] ?? 15));
        $windowSeconds = $lockoutMinutes * 60;
        $now = time();

        return $this->withLockedStore(function (array &$store) use ($ip, $email, $maxAttempts, $windowSeconds, $now): bool {
            $this->pruneExpired($store, $now, $windowSeconds);

            foreach ($this->keysFor($ip, $email) as $key) {
                $attempts = $store['attempts'][$key] ?? [];
                if (!is_array($attempts)) {
                    $attempts = [];
                }
                $attempts[] = $now;
                $store['attempts'][$key] = $attempts;
            }

            $status = $this->statusFromStore($store, $ip, $email, $maxAttempts, $windowSeconds, $now);

            return $status['locked'];
        });
    }

    public function clearSuccess(string $ip, string $email): void
    {
        $this->withLockedStore(function (array &$store) use ($ip, $email): void {
            foreach ($this->keysFor($ip, $email) as $key) {
                unset($store['attempts'][$key]);
            }
        });
    }

    /**
     * @return list<string>
     */
    private function keysFor(string $ip, string $email): array
    {
        $normalizedEmail = mb_strtolower(trim($email));

        return [
            'ip:' . $ip,
            'email:' . md5($normalizedEmail),
        ];
    }

    /**
     * @param array<string, mixed> $store
     */
    private function pruneExpired(array &$store, int $now, int $windowSeconds): void
    {
        $attempts = $store['attempts'] ?? [];
        if (!is_array($attempts)) {
            $store['attempts'] = [];

            return;
        }

        foreach ($attempts as $key => $timestamps) {
            if (!is_array($timestamps)) {
                unset($attempts[$key]);
                continue;
            }

            $fresh = array_values(array_filter(
                $timestamps,
                static fn (mixed $ts): bool => is_int($ts) && $ts > ($now - $windowSeconds)
            ));

            if ($fresh === []) {
                unset($attempts[$key]);
            } else {
                $attempts[$key] = $fresh;
            }
        }

        $store['attempts'] = $attempts;
    }

    /**
     * @param array<string, mixed> $store
     * @return array{locked: bool, retryAfter: int, reason: string|null}
     */
    private function statusFromStore(
        array $store,
        string $ip,
        string $email,
        int $maxAttempts,
        int $windowSeconds,
        int $now
    ): array {
        foreach ($this->keysFor($ip, $email) as $key) {
            $attempts = $store['attempts'][$key] ?? [];
            if (!is_array($attempts)) {
                continue;
            }

            $recent = array_values(array_filter(
                $attempts,
                static fn (mixed $ts): bool => is_int($ts) && $ts > ($now - $windowSeconds)
            ));

            if (count($recent) >= $maxAttempts) {
                $oldest = min($recent);
                $retryAfter = max(1, ($oldest + $windowSeconds) - $now);

                return [
                    'locked' => true,
                    'retryAfter' => $retryAfter,
                    'reason' => str_starts_with($key, 'ip:') ? 'ip' : 'email',
                ];
            }
        }

        return ['locked' => false, 'retryAfter' => 0, 'reason' => null];
    }

    /**
     * @template T
     * @param callable(array<string, mixed>): T $callback
     * @return T
     */
    private function withLockedStore(callable $callback): mixed
    {
        $this->ensureStorage();

        $handle = fopen($this->absolutePath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Nepodarilo sa otvoriť login attempts store: ' . $this->absolutePath);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Nepodarilo sa získať zámok login attempts store.');
            }

            $store = $this->readStore($handle);
            $before = JsonHelper::encode($store);
            $result = $callback($store);
            $after = JsonHelper::encode($store);

            if ($after !== $before) {
                $this->writeStore($handle, $store);
            }

            return $result;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function ensureStorage(): void
    {
        $dir = dirname($this->absolutePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (!file_exists($this->absolutePath)) {
            file_put_contents($this->absolutePath, JsonHelper::encode(['attempts' => []], JSON_PRETTY_PRINT));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function readStore(mixed $handle): array
    {
        rewind($handle);
        $raw = stream_get_contents($handle);
        if ($raw === false || trim($raw) === '') {
            return ['attempts' => []];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : ['attempts' => []];
    }

    /**
     * @param array<string, mixed> $store
     */
    private function writeStore(mixed $handle, array $store): void
    {
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, JsonHelper::encode($store, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($handle);
    }
}
