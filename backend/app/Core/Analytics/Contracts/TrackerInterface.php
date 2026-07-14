<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Analytics\Contracts;

use PaginiumCMS\Core\Analytics\Models\Visit;
use PaginiumCMS\Core\Analytics\Models\Visitor;

/**
 * Rozhranie pre trackovanie návštev.
 */
interface TrackerInterface
{
    /**
     * Zaznamená návštevu.
     */
    public function track(Visit $visit): void;

    /**
     * Získa návštevníka podľa ID.
     */
    public function getVisitor(string $visitorId): ?Visitor;

    /**
     * Získa zoznam návštev pre daný deň.
     */
    public function getVisits(string $date = null, int $limit = 100): array;

    /**
     * Získa denné štatistiky.
     */
    public function getDailyStats(string $date = null): array;

    /**
     * Získa návštevníkov v reálnom čase.
     */
    public function getRealtimeVisitors(): array;
}
