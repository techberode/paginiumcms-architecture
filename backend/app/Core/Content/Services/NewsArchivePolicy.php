<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Content\Services;

use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\FlatFile\Models\ContentIndexEntry;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * News → archive category rules (public feed vs highlights).
 */
final class NewsArchivePolicy
{
    public const CATEGORY_NEWS = 'news';

    public const CATEGORY_ARCHIVE = 'archive';

    public const MAX_RETENTION_DAYS = 45;

    public const MIN_RETENTION_DAYS = 1;

    public function __construct(private SettingsRepositoryInterface $settings)
    {
    }

    public function isHighlightExcludedCategory(string $category): bool
    {
        return mb_strtolower(trim($category)) === self::CATEGORY_ARCHIVE;
    }

    public function autoArchiveEnabled(): bool
    {
        $scheduler = $this->settings->group('scheduler');

        return (bool) ($scheduler['newsAutoArchiveEnabled'] ?? true);
    }

    public function defaultRetentionDays(): int
    {
        $scheduler = $this->settings->group('scheduler');
        $value = (int) ($scheduler['newsRetentionDaysDefault'] ?? 21);

        return $this->clampRetentionDays($value);
    }

    public function clampRetentionDays(int $days): int
    {
        return max(self::MIN_RETENTION_DAYS, min(self::MAX_RETENTION_DAYS, $days));
    }

    public function resolveRetentionDays(Article $article): int
    {
        $override = $article->getNewsRetentionDays();
        if ($override !== null) {
            return $this->clampRetentionDays($override);
        }

        return $this->defaultRetentionDays();
    }

    public function resolveNewsAnchorDate(Article $article): ?\DateTimeImmutable
    {
        $frontMatter = $article->getFrontMatter();
        $candidates = [
            ContentIndexEntry::normalizeIndexedDate($frontMatter['date'] ?? null),
            ContentIndexEntry::normalizeIndexedDate($frontMatter['createdAt'] ?? null),
            ContentIndexEntry::normalizeIndexedDate($frontMatter['updatedAt'] ?? null),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === null || $candidate === '') {
                continue;
            }

            try {
                return new \DateTimeImmutable($candidate);
            } catch (\Exception) {
                continue;
            }
        }

        return null;
    }

    public function isNewsArchiveDue(Article $article, ?\DateTimeImmutable $now = null): bool
    {
        if (!$article->isPublished()) {
            return false;
        }

        if (mb_strtolower($article->getCategory()) !== self::CATEGORY_NEWS) {
            return false;
        }

        $anchor = $this->resolveNewsAnchorDate($article);
        if ($anchor === null) {
            return false;
        }

        $now ??= new \DateTimeImmutable('today');
        $threshold = $anchor->modify('+' . $this->resolveRetentionDays($article) . ' days');

        return $threshold <= $now;
    }
}
