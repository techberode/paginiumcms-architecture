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
     */
    public function getOverview(string $period = 'today'): array;

    /**
     * Získa top stránky.
     */
    public function getTopPages(int $limit = 10, string $period = 'today'): array;

    /**
     * Získa top referery.
     */
    public function getTopReferers(int $limit = 10, string $period = 'today'): array;

    /**
     * Získa štatistiky zariadení.
     */
    public function getDeviceStats(string $period = 'today'): array;

    /**
     * Získa geo štatistiky.
     */
    public function getGeoStats(string $period = 'today'): array;

    /**
     * Získa denné štatistiky pre graf.
     */
    public function getDailyChart(int $days = 30): array;
}
