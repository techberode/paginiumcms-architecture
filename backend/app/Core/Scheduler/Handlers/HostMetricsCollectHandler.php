<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler\Handlers;

use PaginiumCMS\Core\Performance\HostMetricsCollector;
use PaginiumCMS\Core\Performance\HostMetricsSettings;
use PaginiumCMS\Core\Performance\HostMetricsStore;
use PaginiumCMS\Core\Scheduler\Contracts\JobHandlerInterface;
use PaginiumCMS\Core\Scheduler\Models\JobRunResult;

final class HostMetricsCollectHandler implements JobHandlerInterface
{
    public function __construct(
        private HostMetricsSettings $settings,
        private HostMetricsCollector $collector,
        private HostMetricsStore $store
    ) {
    }

    public function key(): string
    {
        return 'host_metrics.collect';
    }

    public function label(): string
    {
        return 'Host metrics snapshot (It.82d)';
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handle(array $payload = []): JobRunResult
    {
        if (!$this->settings->enabled()) {
            return new JobRunResult(
                true,
                'Host metrics disabled in engine settings (skipped).',
                ['skipped' => 'engine_disabled']
            );
        }

        $diskPath = trim((string) ($payload['disk_path'] ?? ''));
        $snapshot = $this->collector->collect($diskPath !== '' ? $diskPath : null);
        if ($snapshot === null) {
            return new JobRunResult(
                false,
                'Unable to collect host metrics on this environment (no /proc or disk probe).',
                []
            );
        }

        $this->store->save($snapshot);
        $at = (string) ($snapshot['collected_at'] ?? '');

        return new JobRunResult(
            true,
            $at !== '' ? 'Host snapshot saved at ' . $at . '.' : 'Host snapshot saved.',
            ['collected_at' => $at !== '' ? $at : null]
        );
    }
}
