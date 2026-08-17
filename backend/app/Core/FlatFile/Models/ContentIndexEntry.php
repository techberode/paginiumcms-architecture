<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Models;

use DateTimeInterface;

/**
 * Záznam v flat-file content indexe (Iterácia 19).
 *
 * @phpstan-type ContentIndexEntryArray array{
 *     slug: string,
 *     type: string,
 *     title: string,
 *     status: string,
 *     author: string,
 *     path: string,
 *     excerpt: string,
 *     tags: list<string>,
 *     category?: string,
 *     updatedAt: string,
 *     createdAt: string,
 *     scheduledAt?: string,
 *     lastReviewedAt?: string,
 *     defaultLocale?: string,
 *     locales?: list<string>,
 *     localeStatus?: array<string, string>
 * }
 */
final class ContentIndexEntry
{
    /**
     * @param list<string> $tags
     * @param list<string> $locales
     * @param array<string, string> $localeStatus
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $type,
        public readonly string $title,
        public readonly string $status,
        public readonly string $author,
        public readonly string $path,
        public readonly string $excerpt,
        public readonly array $tags,
        public readonly string $updatedAt,
        public readonly string $createdAt,
        public readonly string $category = '',
        public readonly string $scheduledAt = '',
        public readonly string $lastReviewedAt = '',
        public readonly string $defaultLocale = 'sk',
        public readonly array $locales = [],
        public readonly array $localeStatus = [],
    ) {
    }

    /**
     * @param array<string, mixed>|null $canonical LocalizedContentNormalizer output.
     */
    public static function fromContent(
        Content $content,
        string $type,
        string $excerpt = '',
        ?array $canonical = null,
    ): self {
        $frontMatter = $content->getFrontMatter();
        $modifiedAt = $content->getModifiedAt() > 0
            ? date('c', $content->getModifiedAt())
            : date('c');

        $tags = [];
        if ($content instanceof Article) {
            $tags = self::normalizeTags($content->getTags());
        }

        $category = trim($content->getCategory());

        if ($excerpt === '' && $content instanceof Article) {
            $excerpt = $content->getExcerpt(160);
        }

        $createdAt = self::normalizeIndexedDate($frontMatter['createdAt'] ?? null) ?? $modifiedAt;
        if ($content instanceof Article) {
            $articleDate = self::normalizeIndexedDate($frontMatter['date'] ?? null);
            if ($articleDate !== null) {
                $createdAt = $articleDate;
            }
        }

        $scheduledAt = '';
        $rawScheduledAt = $frontMatter['scheduledAt'] ?? null;
        if (is_string($rawScheduledAt) && trim($rawScheduledAt) !== '') {
            $scheduledAt = trim($rawScheduledAt);
        } elseif ($content->getScheduledAt() !== null) {
            $scheduledAt = $content->getScheduledAt()->format('c');
        }

        $lastReviewedAt = '';
        $rawLastReviewedAt = $frontMatter['lastReviewedAt'] ?? null;
        if (is_string($rawLastReviewedAt) && trim($rawLastReviewedAt) !== '') {
            $lastReviewedAt = trim($rawLastReviewedAt);
        }

        $defaultLocale = 'sk';
        $locales = [];
        $localeStatus = [];
        if (is_array($canonical)) {
            $defaultLocale = (string) ($canonical['defaultLocale'] ?? 'sk');
            /** @var array<string, array<string, mixed>> $localized */
            $localized = is_array($canonical['localizedContent'] ?? null) ? $canonical['localizedContent'] : [];
            $locales = array_keys($localized);
            /** @var array<string, string> $rawStatus */
            $rawStatus = is_array($canonical['localeStatus'] ?? null) ? $canonical['localeStatus'] : [];
            foreach ($rawStatus as $code => $localeState) {
                $localeStatus[(string) $code] = (string) $localeState;
            }
        }

        return new self(
            slug: $content->getSlug(),
            type: $type,
            title: $content->getTitle(),
            status: $content->getStatus(),
            author: $content->getAuthor(),
            path: $content->getPath(),
            excerpt: $excerpt,
            tags: $tags,
            updatedAt: is_string($frontMatter['updatedAt'] ?? null) ? $frontMatter['updatedAt'] : $modifiedAt,
            createdAt: $createdAt,
            category: $category,
            scheduledAt: $scheduledAt,
            lastReviewedAt: $lastReviewedAt,
            defaultLocale: $defaultLocale,
            locales: $locales,
            localeStatus: $localeStatus,
        );
    }

    public function matchesStatusFilter(string $status, ?string $locale = null): bool
    {
        if ($status === '') {
            return true;
        }

        if ($this->localeStatus !== []) {
            if ($locale !== null && $locale !== '') {
                return ($this->localeStatus[$locale] ?? 'draft') === $status;
            }

            if ($status === 'published') {
                foreach ($this->localeStatus as $localeState) {
                    if ($localeState === 'published') {
                        return true;
                    }
                }

                return false;
            }

            return ($this->localeStatus[$this->defaultLocale] ?? $this->status) === $status;
        }

        return $this->status === $status;
    }

    /**
     * Date used for editorial calendar placement (It.81d).
     */
    public function calendarDate(): string
    {
        if ($this->status === 'scheduled' && $this->scheduledAt !== '') {
            $scheduled = self::normalizeIndexedDate($this->scheduledAt);
            if ($scheduled !== null) {
                return $scheduled;
            }
        }

        if ($this->status === 'published') {
            $published = self::normalizeIndexedDate($this->createdAt);
            if ($published !== null) {
                return $published;
            }
        }

        $updated = self::normalizeIndexedDate($this->updatedAt);
        if ($updated !== null) {
            return $updated;
        }

        return self::normalizeIndexedDate($this->createdAt) ?? date('Y-m-d');
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $rawTags = $data['tags'] ?? [];
        $tags = self::normalizeTags($rawTags);
        /** @var list<string> $locales */
        $locales = is_array($data['locales'] ?? null) ? array_values(array_map('strval', $data['locales'])) : [];
        /** @var array<string, string> $localeStatus */
        $localeStatus = [];
        if (is_array($data['localeStatus'] ?? null)) {
            foreach ($data['localeStatus'] as $code => $state) {
                $localeStatus[(string) $code] = (string) $state;
            }
        }

        return new self(
            slug: (string) ($data['slug'] ?? ''),
            type: (string) ($data['type'] ?? 'page'),
            title: (string) ($data['title'] ?? ''),
            status: (string) ($data['status'] ?? 'draft'),
            author: (string) ($data['author'] ?? ''),
            path: (string) ($data['path'] ?? ''),
            excerpt: (string) ($data['excerpt'] ?? ''),
            tags: $tags,
            updatedAt: (string) ($data['updatedAt'] ?? date('c')),
            createdAt: (string) ($data['createdAt'] ?? date('c')),
            category: trim((string) ($data['category'] ?? '')),
            scheduledAt: (string) ($data['scheduledAt'] ?? ''),
            lastReviewedAt: (string) ($data['lastReviewedAt'] ?? ''),
            defaultLocale: (string) ($data['defaultLocale'] ?? 'sk'),
            locales: $locales,
            localeStatus: $localeStatus,
        );
    }

    /**
     * @return ContentIndexEntryArray
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'type' => $this->type,
            'title' => $this->title,
            'status' => $this->status,
            'author' => $this->author,
            'path' => $this->path,
            'excerpt' => $this->excerpt,
            'tags' => $this->tags,
            'category' => $this->category,
            'updatedAt' => $this->updatedAt,
            'createdAt' => $this->createdAt,
            'scheduledAt' => $this->scheduledAt,
            'lastReviewedAt' => $this->lastReviewedAt,
            'defaultLocale' => $this->defaultLocale,
            'locales' => $this->locales,
            'localeStatus' => $this->localeStatus,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchResult(): array
    {
        return [
            'slug' => $this->slug,
            'type' => $this->type,
            'title' => $this->title,
            'status' => $this->status,
            'excerpt' => $this->excerpt,
            'tags' => $this->tags,
            'category' => $this->category,
            'updatedAt' => $this->updatedAt,
            'path' => $this->path,
            'defaultLocale' => $this->defaultLocale,
            'locales' => $this->locales,
            'localeStatus' => $this->localeStatus,
        ];
    }

    /** @return list<string> */
    public static function normalizeTags(mixed $raw): array
    {
        if (is_array($raw)) {
            $tags = $raw;
        } elseif (is_string($raw)) {
            $value = trim($raw);
            $value = trim($value, '[]');
            $tags = $value === '' ? [] : (preg_split('/\s*,\s*/', $value) ?: []);
        } else {
            return [];
        }

        $normalized = [];
        foreach ($tags as $tag) {
            if (!is_string($tag) && !is_int($tag)) {
                continue;
            }
            $text = trim((string) $tag);
            if ($text !== '') {
                $normalized[] = $text;
            }
        }

        return array_values(array_unique($normalized));
    }

    public static function normalizeIndexedDate(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_int($value) || is_float($value)) {
            return date('Y-m-d', (int) $value);
        }

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $trimmed = trim($value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $trimmed) === 1) {
            return substr($trimmed, 0, 10);
        }

        $timestamp = strtotime($trimmed);

        return $timestamp !== false ? date('Y-m-d', $timestamp) : null;
    }
}
