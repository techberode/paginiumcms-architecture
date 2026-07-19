<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security\Firewall;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Flat-file ban register and whitelist (Iteration 50).
 */
final class FirewallBanStore
{
    private string $bansPath;
    private string $whitelistPath;

    public function __construct(
        private FileReaderInterface $reader,
        private SettingsRepositoryInterface $settings,
        private string $bansFile = 'data/security/firewall/bans.json',
        private string $whitelistFile = 'data/security/firewall/whitelist.json'
    ) {
        $base = rtrim($this->reader->getBasePath(), '/');
        $this->bansPath = $base . '/' . ltrim($this->bansFile, '/');
        $this->whitelistPath = $base . '/' . ltrim($this->whitelistFile, '/');
    }

    public function isWhitelisted(string $ip): bool
    {
        return $this->withLockedWhitelist(function (array $store) use ($ip): bool {
            $ips = $store['ips'] ?? [];
            if (!is_array($ips)) {
                return false;
            }

            return in_array($ip, $ips, true);
        });
    }

    /**
     * @return list<string>
     */
    public function listWhitelist(): array
    {
        return $this->withLockedWhitelist(function (array $store): array {
            $ips = $store['ips'] ?? [];

            return is_array($ips) ? array_values($ips) : [];
        });
    }

    public function addWhitelist(string $ip): void
    {
        $this->withLockedWhitelist(function (array &$store) use ($ip): void {
            $ips = $store['ips'] ?? [];
            if (!is_array($ips)) {
                $ips = [];
            }
            if (!in_array($ip, $ips, true)) {
                $ips[] = $ip;
            }
            $store['ips'] = array_values($ips);
        });
    }

    public function removeWhitelist(string $ip): bool
    {
        return $this->withLockedWhitelist(function (array &$store) use ($ip): bool {
            $ips = $store['ips'] ?? [];
            if (!is_array($ips)) {
                return false;
            }

            $before = count($ips);
            $store['ips'] = array_values(array_filter(
                $ips,
                static fn (mixed $entry): bool => is_string($entry) && $entry !== $ip
            ));

            return count($store['ips']) < $before;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getBan(string $ip): ?array
    {
        return $this->withLockedBans(function (array $store) use ($ip): ?array {
            $this->pruneExpiredBans($store);
            $ban = $store['bans'][$ip] ?? null;

            return is_array($ban) ? $ban : null;
        });
    }

    public function isBanned(string $ip): bool
    {
        $ban = $this->getBan($ip);
        if ($ban === null) {
            return false;
        }

        if (($ban['permanent'] ?? false) === true) {
            return true;
        }

        $expiresAt = $ban['expires_at'] ?? null;
        if ($expiresAt === null) {
            return true;
        }

        return is_int($expiresAt) && $expiresAt > time();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listBans(bool $activeOnly = true): array
    {
        return $this->withLockedBans(function (array $store) use ($activeOnly): array {
            $this->pruneExpiredBans($store);
            $result = [];

            foreach ($store['bans'] ?? [] as $ip => $ban) {
                if (!is_string($ip) || !is_array($ban)) {
                    continue;
                }

                $permanent = ($ban['permanent'] ?? false) === true;
                $expiresAt = $ban['expires_at'] ?? null;
                $active = $permanent || (is_int($expiresAt) && $expiresAt > time());

                if ($activeOnly && !$active) {
                    continue;
                }

                $result[] = array_merge($ban, ['ip' => $ip, 'active' => $active]);
            }

            return $result;
        });
    }

    public function countActiveJails(): int
    {
        $now = time();

        return $this->withLockedBans(function (array $store) use ($now): int {
            $this->pruneExpiredBans($store);
            $count = 0;

            foreach ($store['bans'] ?? [] as $ban) {
                if (!is_array($ban)) {
                    continue;
                }
                if (($ban['permanent'] ?? false) === true) {
                    continue;
                }
                $expiresAt = $ban['expires_at'] ?? null;
                if (is_int($expiresAt) && $expiresAt > $now) {
                    ++$count;
                }
            }

            return $count;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function applyManualBan(string $ip, bool $permanent, string $reason = 'manual'): array
    {
        return $this->withLockedBans(function (array &$store) use ($ip, $permanent, $reason): array {
            $settings = $this->settings->group('firewall');
            $jailMinutes = max(1, (int) ($settings['jailMinutes'] ?? 15));

            $ban = [
                'expires_at' => $permanent ? null : time() + ($jailMinutes * 60),
                'permanent' => $permanent,
                'score' => (int) ($store['sin_scores'][$ip] ?? 0),
                'reason' => $reason,
                'updated_at' => gmdate('c'),
            ];

            if (!isset($store['bans']) || !is_array($store['bans'])) {
                $store['bans'] = [];
            }

            $store['bans'][$ip] = $ban;

            return array_merge($ban, ['ip' => $ip, 'active' => true]);
        });
    }

    public function unban(string $ip): bool
    {
        return $this->withLockedBans(function (array &$store) use ($ip): bool {
            if (!isset($store['bans'][$ip])) {
                return false;
            }

            unset($store['bans'][$ip]);
            unset($store['sin_scores'][$ip]);
            unset($store['recent_incidents'][$ip]);

            return true;
        });
    }

    /**
     * Record a scenario violation and apply jail / permanent escalation when thresholds are met.
     *
     * @return array{banned: bool, permanent: bool, ban: array<string, mixed>|null}
     */
    public function recordViolation(string $ip, string $scenarioId): array
    {
        $settings = $this->settings->group('firewall');
        $jailMinutes = max(1, (int) ($settings['jailMinutes'] ?? 15));
        $maxRetries = max(1, (int) ($settings['maxRetries'] ?? 3));
        $permanentThreshold = max(1, (int) ($settings['permanentThreshold'] ?? 3));
        $windowSeconds = $jailMinutes * 60;
        $now = time();

        return $this->withLockedBans(function (array &$store) use (
            $ip,
            $scenarioId,
            $jailMinutes,
            $maxRetries,
            $permanentThreshold,
            $windowSeconds,
            $now
        ): array {
            $this->pruneExpiredBans($store);

            if (!isset($store['recent_incidents']) || !is_array($store['recent_incidents'])) {
                $store['recent_incidents'] = [];
            }

            $incidents = $store['recent_incidents'][$ip] ?? [];
            if (!is_array($incidents)) {
                $incidents = [];
            }

            $incidents[] = $now;
            $incidents = array_values(array_filter(
                $incidents,
                static fn (mixed $ts): bool => is_int($ts) && $ts > ($now - $windowSeconds)
            ));
            $store['recent_incidents'][$ip] = $incidents;

            if (count($incidents) < $maxRetries) {
                return ['banned' => false, 'permanent' => false, 'ban' => null];
            }

            if (!isset($store['sin_scores']) || !is_array($store['sin_scores'])) {
                $store['sin_scores'] = [];
            }

            $previousBan = $store['bans'][$ip] ?? null;
            $previousExpiresAt = is_array($previousBan) ? ($previousBan['expires_at'] ?? null) : null;
            $hadExpiredJail = is_array($previousBan)
                && ($previousBan['permanent'] ?? false) !== true
                && is_int($previousExpiresAt)
                && $previousExpiresAt <= $now;

            $sinScore = (int) ($store['sin_scores'][$ip] ?? 0);
            if ($hadExpiredJail || $sinScore > 0) {
                ++$sinScore;
            } else {
                $sinScore = 1;
            }

            $store['sin_scores'][$ip] = $sinScore;
            $permanent = $sinScore >= $permanentThreshold;

            $ban = [
                'expires_at' => $permanent ? null : $now + ($jailMinutes * 60),
                'permanent' => $permanent,
                'score' => $sinScore,
                'reason' => $scenarioId,
                'updated_at' => gmdate('c'),
            ];

            if (!isset($store['bans']) || !is_array($store['bans'])) {
                $store['bans'] = [];
            }

            $store['bans'][$ip] = $ban;
            $store['recent_incidents'][$ip] = [];

            return [
                'banned' => true,
                'permanent' => $permanent,
                'ban' => array_merge($ban, ['ip' => $ip, 'active' => true]),
            ];
        });
    }

    /**
     * @param array<string, mixed> $store
     */
    private function pruneExpiredBans(array &$store): void
    {
        $now = time();
        $bans = $store['bans'] ?? [];
        if (!is_array($bans)) {
            $store['bans'] = [];

            return;
        }

        foreach ($bans as $ip => $ban) {
            if (!is_array($ban) || ($ban['permanent'] ?? false) === true) {
                continue;
            }

            $expiresAt = $ban['expires_at'] ?? null;
            if (is_int($expiresAt) && $expiresAt <= $now) {
                unset($bans[$ip]);
            }
        }

        $store['bans'] = $bans;
    }

    /**
     * @template T
     * @param callable(array<string, mixed>): T $callback
     * @return T
     */
    private function withLockedBans(callable $callback): mixed
    {
        $this->ensureBansStorage();

        $handle = fopen($this->bansPath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Nepodarilo sa otvoriť firewall bans store: ' . $this->bansPath);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Nepodarilo sa získať zámok firewall bans store.');
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

    /**
     * @template T
     * @param callable(array<string, mixed>): T $callback
     * @return T
     */
    private function withLockedWhitelist(callable $callback): mixed
    {
        $this->ensureWhitelistStorage();

        $handle = fopen($this->whitelistPath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Nepodarilo sa otvoriť firewall whitelist store: ' . $this->whitelistPath);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Nepodarilo sa získať zámok firewall whitelist store.');
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

    private function ensureBansStorage(): void
    {
        $dir = dirname($this->bansPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (!file_exists($this->bansPath)) {
            file_put_contents(
                $this->bansPath,
                JsonHelper::encode(['bans' => [], 'sin_scores' => [], 'recent_incidents' => []], JSON_PRETTY_PRINT)
            );
        }
    }

    private function ensureWhitelistStorage(): void
    {
        $dir = dirname($this->whitelistPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (!file_exists($this->whitelistPath)) {
            file_put_contents($this->whitelistPath, JsonHelper::encode(['ips' => []], JSON_PRETTY_PRINT));
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
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
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
