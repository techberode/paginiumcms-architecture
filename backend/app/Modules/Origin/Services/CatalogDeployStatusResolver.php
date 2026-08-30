<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Services;

use PaginiumCMS\Support\AppVersion;

/**
 * Maps catalog iteration metadata to deploy visibility on the running instance (It.82e+).
 */
final class CatalogDeployStatusResolver
{
    /**
     * @param array<string, mixed> $iteration
     */
    public function resolveForIteration(array $iteration, int $percentComplete): string
    {
        $current = AppVersion::current();
        $since = isset($iteration['since']) ? trim((string) $iteration['since']) : '';
        $target = isset($iteration['targetVersion']) ? trim((string) $iteration['targetVersion']) : '';
        $phase = (string) ($iteration['phase'] ?? 'planned');

        if ($phase === 'planned') {
            return 'planned';
        }

        if ($since !== '' && $this->versionAtLeast($current, $since)) {
            return $percentComplete >= 100 ? 'live' : 'partial_live';
        }

        if ($target !== '' && $this->versionAtLeast($current, $target)) {
            return $percentComplete >= 100 ? 'live' : 'partial_live';
        }

        if ($since !== '' || $target !== '') {
            return 'pending_deploy';
        }

        if ($percentComplete >= 100) {
            return 'unreleased';
        }

        return $phase === 'partial' ? 'in_progress' : 'planned';
    }

    /**
     * @return array{appVersion: string, environment: string}
     */
    public function runtimeContext(): array
    {
        $env = getenv('APP_ENV');

        return [
            'appVersion' => AppVersion::current(),
            'environment' => is_string($env) && $env !== '' ? $env : 'production',
        ];
    }

    private function versionAtLeast(string $current, string $required): bool
    {
        $current = ltrim(trim($current), 'vV');
        $required = ltrim(trim($required), 'vV');

        if ($current === '' || $required === '') {
            return false;
        }

        return version_compare($current, $required, '>=');
    }
}
