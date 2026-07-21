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
     *
     * @return array<string, mixed>
     */
    public function getOverview(string $period = 'today'): array;

    /**
     * Získa top stránky.
     *
     * @return list<array{uri: string, views: int}>
     */
    public function getTopPages(int $limit = 10, string $period = 'today'): array;

    /**
     * Získa top referery.
     *
     * @return list<array{referer: string, visits: int}>
     */
    public function getTopReferers(int $limit = 10, string $period = 'today'): array;

    /**
     * Získa štatistiky zariadení.
     *
     * @return array<string, int>
     */
    public function getDeviceStats(string $period = 'today'): array;

    /**
     * @return list<array{browser: string, visits: int}>
     */
    public function getBrowserStats(string $period = 'today'): array;

    /**
     * Získa geo štatistiky.
     *
     * @return list<array{country: string, visits: int}>
     */
    public function getGeoStats(string $period = 'today'): array;

    /**
     * Získa denné štatistiky pre graf.
     *
     * @return list<array<string, mixed>>
     */
    public function getDailyChart(int $days = 30): array;

    /**
     * Získa top IP adresy.
     *
     * @return list<array{ip: string, visits: int, top_uri: string}>
     */
    public function getTopIpStats(int $limit = 15, string $period = 'today'): array;

    /**
     * Získa top články (heuristika podľa URI).
     *
     * @return list<array{uri: string, views: int, title: string}>
     */
    public function getTopArticles(int $limit = 10, string $period = 'today'): array;

    /**
     * @return array<string, mixed>
     */
    public function getAggregatedOverview(string $interval = 'day'): array;
}
