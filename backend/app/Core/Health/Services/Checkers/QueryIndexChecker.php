<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Health\Services\Checkers;

use PaginiumCMS\Core\Health\Contracts\HealthCheckInterface;
use PaginiumCMS\Core\Health\Models\HealthStatus;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexCapabilityProbe;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexInterface;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Derived SQLite query index health (It.92e). JSON index remains primary SSOT projection.
 */
final class QueryIndexChecker implements HealthCheckInterface
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private QueryIndexCapabilityProbe $probe
    ) {
    }

    public function getName(): string
    {
        return 'query_index';
    }

    public function getDescription(): string
    {
        return 'Optional SQLite catalog index (derived)';
    }

    public function getGroup(): string
    {
        return 'storage';
    }

    public function check(): HealthStatus
    {
        $start = microtime(true);
        $engine = $this->settings->group('engine');
        $configured = (string) ($engine['queryIndexDriver'] ?? QueryIndexInterface::DRIVER_JSON);
        $probe = $this->probe->probe($engine);
        $active = (string) ($probe['queryIndexDriver']['active'] ?? QueryIndexInterface::DRIVER_JSON);

        $data = [
            'configured_driver' => $configured,
            'active_driver' => $active,
            'json_entries' => $probe['counts']['jsonEntries'] ?? 0,
            'sqlite_entries' => $probe['counts']['sqliteEntries'] ?? 0,
        ];

        $issues = [];
        if ($configured === QueryIndexInterface::DRIVER_SQLITE && $active !== QueryIndexInterface::DRIVER_SQLITE) {
            $issues[] = 'SQLite driver configured but runtime fell back to JSON';
        }

        if ($configured === QueryIndexInterface::DRIVER_SQLITE) {
            $integrity = $probe['capabilities']['integrity']['status'] ?? '';
            if ($integrity === 'failing') {
                $issues[] = 'SQLite integrity check failed — rebuild recommended';
            }

            $jsonCount = (int) ($probe['counts']['jsonEntries'] ?? 0);
            $sqliteCount = (int) ($probe['counts']['sqliteEntries'] ?? 0);
            if ($sqliteCount > 0 && $jsonCount !== $sqliteCount) {
                $issues[] = 'SQLite entry count lags JSON index';
            }
        }

        $status = $issues === [] ? HealthStatus::STATUS_PASS : HealthStatus::STATUS_WARN;
        $message = $issues === []
            ? 'Query index OK'
            : implode('; ', $issues);

        $check = new HealthStatus($this->getName(), $status, $message);
        $check->setData($data);
        $check->setDuration(microtime(true) - $start);

        return $check;
    }
}
