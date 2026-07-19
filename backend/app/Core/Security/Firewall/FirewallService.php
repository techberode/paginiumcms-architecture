<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security\Firewall;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Orchestrates WAF detection, incident logging, and ban escalation.
 */
final class FirewallService
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private FirewallScanner $scanner,
        private FirewallBanStore $banStore,
        private FirewallIncidentLogger $incidentLogger
    ) {
    }

    public function isEnabled(): bool
    {
        $firewall = $this->settings->group('firewall');

        return (bool) ($firewall['enabled'] ?? true);
    }

    public function isWhitelisted(string $ip): bool
    {
        return $this->banStore->isWhitelisted($ip);
    }

    public function isBanned(string $ip): bool
    {
        if ($this->banStore->isWhitelisted($ip)) {
            return false;
        }

        return $this->banStore->isBanned($ip);
    }

    /**
     * @return array<string, mixed>|null Matched scenario when violation recorded.
     */
    public function inspectRequest(string $ip, string $uriPath, string $queryString, string $userAgent): ?array
    {
        if (!$this->isEnabled() || $this->banStore->isWhitelisted($ip)) {
            return null;
        }

        $scenario = $this->scanner->scan($uriPath, $queryString, $userAgent);
        if ($scenario === null) {
            return null;
        }

        $scenarioId = (string) ($scenario['id'] ?? 'unknown');
        $this->incidentLogger->log($ip, $scenarioId, [
            'uri' => $uriPath . ($queryString !== '' ? '?' . $queryString : ''),
            'user_agent' => $userAgent,
        ]);
        $this->banStore->recordViolation($ip, $scenarioId);

        return $scenario;
    }

    /**
     * @return array<string, int>
     */
    public function stats(): array
    {
        $bans = $this->banStore->listBans(true);
        $permanent = 0;
        $jails = 0;

        foreach ($bans as $ban) {
            if (($ban['permanent'] ?? false) === true) {
                ++$permanent;
            } else {
                ++$jails;
            }
        }

        return [
            'active_jails' => $jails,
            'permanent_bans' => $permanent,
            'incidents_24h' => $this->incidentLogger->countLast24Hours(),
            'whitelist_count' => count($this->banStore->listWhitelist()),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listIncidents(int $limit = 100, int $offset = 0): array
    {
        return $this->incidentLogger->list($limit, $offset);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listBans(bool $activeOnly = true): array
    {
        return $this->banStore->listBans($activeOnly);
    }

    /**
     * @return list<string>
     */
    public function listWhitelist(): array
    {
        return $this->banStore->listWhitelist();
    }

    /**
     * @return array<string, mixed>
     */
    public function manualBan(string $ip, bool $permanent, string $reason = 'manual'): array
    {
        return $this->banStore->applyManualBan($ip, $permanent, $reason);
    }

    public function unban(string $ip): bool
    {
        return $this->banStore->unban($ip);
    }

    public function addWhitelist(string $ip): void
    {
        $this->banStore->addWhitelist($ip);
        $this->banStore->unban($ip);
    }

    public function removeWhitelist(string $ip): bool
    {
        return $this->banStore->removeWhitelist($ip);
    }

    public function countActiveJails(): int
    {
        return $this->banStore->countActiveJails();
    }

    /**
     * @return array<string, mixed>
     */
    public function jailSettings(): array
    {
        $firewall = $this->settings->group('firewall');

        return [
            'jailMode' => (string) ($firewall['jailMode'] ?? 'forbidden'),
            'tarpitSeconds' => max(0, min(2, (int) ($firewall['tarpitSeconds'] ?? 0))),
        ];
    }
}
