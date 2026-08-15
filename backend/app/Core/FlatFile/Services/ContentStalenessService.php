<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Services;

use PaginiumCMS\Core\FlatFile\Models\ContentIndexEntry;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Derived staleness for published content (It.81e).
 */
final class ContentStalenessService
{
    public function __construct(private SettingsRepositoryInterface $settings)
    {
    }

    public function thresholdMonths(): int
    {
        return max(0, (int) $this->settings->get('content.staleReviewMonths', 12));
    }

    /**
     * @return array{isStale: bool, monthsSinceReview: int|null}
     */
    public function evaluate(
        string $status,
        string $lastReviewedAt,
        string $updatedAt,
        string $publishedDate,
        ?\DateTimeImmutable $now = null,
    ): array {
        $threshold = $this->thresholdMonths();
        if ($threshold === 0 || $status !== 'published') {
            return ['isStale' => false, 'monthsSinceReview' => null];
        }

        $anchor = $this->resolveAnchorDate($lastReviewedAt, $updatedAt, $publishedDate);
        if ($anchor === null) {
            return ['isStale' => false, 'monthsSinceReview' => null];
        }

        $months = $this->monthsSince($anchor, $now ?? new \DateTimeImmutable('today'));
        if ($months === null) {
            return ['isStale' => false, 'monthsSinceReview' => null];
        }

        return [
            'isStale' => $months > $threshold,
            'monthsSinceReview' => $months,
        ];
    }

    public function entryIsStale(ContentIndexEntry $entry, ?\DateTimeImmutable $now = null): bool
    {
        return $this->evaluate(
            $entry->status,
            $entry->lastReviewedAt,
            $entry->updatedAt,
            $entry->createdAt,
            $now
        )['isStale'];
    }

    public function resolveAnchorDate(string $lastReviewedAt, string $updatedAt, string $publishedDate): ?string
    {
        foreach ([$lastReviewedAt, $updatedAt, $publishedDate] as $candidate) {
            $normalized = ContentIndexEntry::normalizeIndexedDate($candidate);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        return null;
    }

    public function monthsSince(string $anchorYmd, \DateTimeImmutable $now): ?int
    {
        try {
            $anchor = new \DateTimeImmutable($anchorYmd);
        } catch (\Exception) {
            return null;
        }

        $interval = $anchor->diff($now);
        if ($interval->invert === 1) {
            return 0;
        }

        return ($interval->y * 12) + $interval->m;
    }
}
