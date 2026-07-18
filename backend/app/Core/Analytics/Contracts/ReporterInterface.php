<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Analytics\Contracts;

/**
 * Rozhranie pre generovanie reportov.
 */
interface ReporterInterface
{
    /**
     * Získa prehľad štatistík.
 * @return array<int|string, mixed>
 */public function getOverview(string $period = 'today'): array;

    /**
     * Získa top stránky.
 * @return array<int|string, mixed>
 */public function getTopPages(int $limit = 10, string $period = 'today'): array;

    /**
     * Získa top referery.
 * @return array<int|string, mixed>
 */public function getTopReferers(int $limit = 10, string $period = 'today'): array;

    /**
     * Získa štatistiky zariadení.
 * @return array<int|string, mixed>
 */public function getDeviceStats(string $period = 'today'): array;

    /**
     * Získa geo štatistiky.
 * @return array<int|string, mixed>
 */public function getGeoStats(string $period = 'today'): array;

    /**
     * Získa denné štatistiky pre graf.
 * @return array<int|string, mixed>
 */public function getDailyChart(int $days = 30): array;

    /**
     * Získa top IP adresy.
     * @return array<int|string, mixed>
     */
    public function getTopIpStats(int $limit = 15, string $period = 'today'): array;

    /**
     * Získa top články (heuristika podľa URI).
     * @return array<int|string, mixed>
     */
    public function getTopArticles(int $limit = 10, string $period = 'today'): array;

    /**
     * @return array<string, mixed>
     */
    public function getAggregatedOverview(string $interval = 'day'): array;
}
