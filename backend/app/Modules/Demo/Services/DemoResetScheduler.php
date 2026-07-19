<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Demo\Services;

use RuntimeException;

/**
 * Cron-friendly auto-reset for demo.paginiumcms.com (Iteration 13 v2).
 */
final class DemoResetScheduler
{
    public function __construct(
        private DemoMode $demoMode,
        private DemoStorageService $demoStorage
    ) {
    }

    /**
     * @return array{ran: bool, reason?: string, written?: int}
     */
    public function runIfDue(): array
    {
        if (!$this->demoMode->isEnabled()) {
            return ['ran' => false, 'reason' => 'demo_disabled'];
        }

        $intervalMinutes = $this->demoMode->autoResetMinutes();
        if ($intervalMinutes <= 0) {
            return ['ran' => false, 'reason' => 'auto_reset_disabled'];
        }

        $lastReset = $this->demoStorage->getLastResetTimestamp();
        if ($lastReset === null) {
            return ['ran' => false, 'reason' => 'not_seeded'];
        }

        $dueAt = $lastReset + ($intervalMinutes * 60);
        if (time() < $dueAt) {
            return ['ran' => false, 'reason' => 'not_due'];
        }

        try {
            $result = $this->demoStorage->reset();
        } catch (RuntimeException $e) {
            return ['ran' => false, 'reason' => $e->getMessage()];
        }

        return [
            'ran' => true,
            'written' => $result['written'],
        ];
    }
}
