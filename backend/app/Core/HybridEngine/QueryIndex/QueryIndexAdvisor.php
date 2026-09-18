<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\HybridEngine\QueryIndex;

use PaginiumCMS\Core\Performance\PerformanceAggregator;
use PaginiumCMS\Core\Performance\PerformanceGuardSettings;
use PaginiumCMS\Core\Performance\PerformanceSampleStore;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Performance Guard hint: optional SQLite query index (It.92d). Suggest only — never auto-enables.
 */
final class QueryIndexAdvisor
{
    public const HINT_CODE = 'query_index_sqlite';

    /** @var list<string> */
    private const CATALOG_ROUTE_PREFIXES = [
        '/api/articles',
        '/api/pages',
        '/api/content',
        '/api/search',
    ];

    public function __construct(
        private SettingsRepositoryInterface $settings,
        private PerformanceGuardSettings $guardSettings,
        private PerformanceSampleStore $samples,
        private PerformanceAggregator $aggregator,
        private QueryIndexInterface $queryIndex
    ) {
    }

    /**
     * @return list<array{code: string, message: string, route: string, route_p95_ms: float, threshold_ms: int, catalog_entries: int, settings_group: string}>
     */
    public function activeHints(): array
    {
        $hints = [];
        foreach (self::CATALOG_ROUTE_PREFIXES as $route) {
            $hint = $this->evaluateForRoute($route);
            if ($hint !== null) {
                $hints[] = $hint;
            }
        }

        return $hints;
    }

    /**
     * @return array{code: string, message: string, route: string, route_p95_ms: float, threshold_ms: int, catalog_entries: int, settings_group: string}|null
     */
    public function evaluateForRoute(string $route): ?array
    {
        if (!$this->guardSettings->enabled()) {
            return null;
        }

        if ($this->samples->all() === []) {
            return null;
        }

        $engine = $this->settings->group('engine');
        if ((string) ($engine['queryIndexDriver'] ?? QueryIndexInterface::DRIVER_JSON) !== QueryIndexInterface::DRIVER_JSON) {
            return null;
        }

        if (($engine['queryIndexAdviseEnabled'] ?? true) !== true) {
            return null;
        }

        $minEntries = max(1, (int) ($engine['queryIndexAdviseMinEntries'] ?? 2000));
        $catalogEntries = $this->queryIndex->entryCount();
        if ($catalogEntries < $minEntries) {
            return null;
        }

        if (!$this->isCatalogRoute($route)) {
            return null;
        }

        $thresholdMs = (int) ($engine['queryIndexAdviseListP95Ms'] ?? 0);
        if ($thresholdMs <= 0) {
            $thresholdMs = $this->guardSettings->latencyMsWarning();
        }

        $routeP95 = $this->routeP95Ms($route);
        if ($routeP95 === null || $routeP95 < $thresholdMs) {
            return null;
        }

        return [
            'code' => self::HINT_CODE,
            'message' => 'Enable SQLite query index for faster catalog lists (derived layer; flat files stay SSOT).',
            'route' => $route,
            'route_p95_ms' => $routeP95,
            'threshold_ms' => $thresholdMs,
            'catalog_entries' => $catalogEntries,
            'settings_group' => 'engine',
        ];
    }

    private function isCatalogRoute(string $route): bool
    {
        foreach (self::CATALOG_ROUTE_PREFIXES as $prefix) {
            if ($route === $prefix || str_starts_with($route, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }

    private function routeP95Ms(string $route): ?float
    {
        $summary = $this->aggregator->summary(50);
        foreach ($summary['by_route'] as $row) {
            if ((string) ($row['route'] ?? '') !== $route) {
                continue;
            }
            $p95 = $row['p95_ms'] ?? null;

            return is_numeric($p95) ? (float) $p95 : null;
        }

        return null;
    }
}
