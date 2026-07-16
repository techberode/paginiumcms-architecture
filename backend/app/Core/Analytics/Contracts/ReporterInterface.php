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
}
