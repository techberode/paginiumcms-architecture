<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Performance;

use PaginiumCMS\Core\Cache\Services\CacheCapabilityProbe;
use PaginiumCMS\Core\Cache\CacheDriverFactory;
use PaginiumCMS\Core\Cache\Services\CacheAdminService;
use PaginiumCMS\Core\Security\SecurityLogger;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Allow-listed remediation actions on derived layers only (Iteration 71).
 */
final class SafeRemediationService
{
    private const COOLDOWN_SECONDS = 900;

    public function __construct(
        private PerformanceGuardSettings $settings,
        private CacheAdminService $cacheAdmin,
        private CacheCapabilityProbe $cacheProbe,
        private CacheDriverFactory $cacheFactory,
        private SettingsRepositoryInterface $settingsRepo,
        private SecurityLogger $securityLogger,
        private PerformanceBreachStore $breaches
    ) {
    }

    /**
     * @return list<string>
     */
    public function recommendations(string $route, string $severity): array
    {
        $items = [
            'Review recent admin changes and scheduled jobs for this route group.',
            'Consider purging content cache tags if responses are stale or oversized.',
            'Verify worker/scheduler queue depth if publish or Git jobs are pending.',
        ];

        if (str_contains($route, '/api/content') || str_contains($route, '/api/pages') || str_contains($route, '/api/articles')) {
            $items[] = 'Rebuild content index if list endpoints remain slow after cache purge.';
        }

        if ($severity === 'critical') {
            $items[] = 'Check host metrics separately (It.46) — PHP APM does not replace OS monitoring.';
        }

        return $items;
    }

    /**
     * @param list<string> $recommendations
     * @return array{mode: string, applied: bool, action?: string, detail?: string}
     */
    public function maybeApplyAutomatic(string $route, string $severity, array $recommendations): array
    {
        $mode = $this->settings->remediationMode();
        if ($mode === PerformanceGuardSettings::REMEDIATION_OFF || $mode === PerformanceGuardSettings::REMEDIATION_SUGGEST) {
            return ['mode' => $mode, 'applied' => false];
        }

        if ($this->recentAutomaticCooldown($route)) {
            return ['mode' => $mode, 'applied' => false, 'detail' => 'cooldown_active'];
        }

        $engine = $this->settingsRepo->group('engine');
        $probe = $this->cacheProbe->probe(
            $this->cacheFactory->create(CacheDriverFactory::driverFromEngineSettings($engine)),
            $engine
        );

        $cacheOk = ($probe['health']['ok'] ?? false) === true;
        if (!$cacheOk) {
            return ['mode' => $mode, 'applied' => false, 'detail' => 'cache_capability_failed'];
        }

        // Never auto-enable Redis or change drivers — only purge derived content cache.
        try {
            $result = $this->cacheAdmin->purge(CacheAdminService::SCOPE_CONTENT);
            $this->securityLogger->logSuspiciousActivity(
                'performance_guard.auto_remediation',
                sprintf('Automatic content cache purge after %s breach on %s', $severity, $route)
            );
            $this->markAutomaticApplied($route);

            return [
                'mode' => $mode,
                'applied' => true,
                'action' => 'cache_purge_content',
                'detail' => sprintf(
                    'entries %d → %d',
                    $result['file_entries_before'],
                    $result['file_entries_after']
                ),
            ];
        } catch (\Throwable $e) {
            return ['mode' => $mode, 'applied' => false, 'detail' => $e->getMessage()];
        }
    }

    private function recentAutomaticCooldown(string $route): bool
    {
        foreach ($this->breaches->recent(50) as $breach) {
            if (($breach['route'] ?? '') !== $route) {
                continue;
            }

            $remediation = $breach['remediation'] ?? null;
            if (!is_array($remediation) || ($remediation['applied'] ?? false) !== true) {
                continue;
            }

            $openedAt = strtotime((string) ($breach['opened_at'] ?? ''));
            if ($openedAt !== false && (time() - $openedAt) < self::COOLDOWN_SECONDS) {
                return true;
            }
        }

        return false;
    }

    private function markAutomaticApplied(string $route): void
    {
        $open = $this->breaches->findOpen($route, 'critical') ?? $this->breaches->findOpen($route, 'warning');
        if ($open === null) {
            return;
        }

        $open['remediation_applied_at'] = date('c');
        $this->breaches->save($open);
    }
}
