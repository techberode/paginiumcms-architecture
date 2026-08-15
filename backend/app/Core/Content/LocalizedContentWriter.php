<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Content;

use PaginiumCMS\Core\FlatFile\Models\Content;
use PaginiumCMS\Support\AppTimezone;

/**
 * Merges a locale-scoped write payload into schema v2 front matter (Iteration 73 Phase 2a).
 */
final class LocalizedContentWriter
{
    public function __construct(
        private LocalizedContentNormalizer $normalizer,
    ) {
    }

    /**
     * @param array<int|string, mixed> $data Must include `locale`; title/content/status/SEO fields apply to that locale.
     */
    public function applyLocalePayload(Content $content, array $data, string $slug): void
    {
        $locale = strtolower(trim((string) $data['locale']));

        /** @var array<string, mixed> $canonical */
        $canonical = $this->normalizer->normalize($content);
        $defaultLocale = (string) $canonical['defaultLocale'];
        /** @var array<string, array<string, mixed>> $localizedContent */
        $localizedContent = $canonical['localizedContent'];
        /** @var array<string, string> $localeStatus */
        $localeStatus = $canonical['localeStatus'];

        $localizedContent[$locale] = [
            'title' => (string) ($data['title'] ?? ''),
            'body' => (string) ($data['content'] ?? ''),
            'seo' => $this->buildSeoSlice($data),
        ];
        $localeStatus[$locale] = (string) ($data['status'] ?? $localeStatus[$locale] ?? 'draft');

        $writtenSlice = $localizedContent[$locale];
        $defaultSlice = $localizedContent[$defaultLocale] ?? null;
        if (
            $locale !== $defaultLocale
            && $this->sliceIsEmpty(is_array($defaultSlice) ? $defaultSlice : null)
            && !$this->sliceIsEmpty($writtenSlice)
        ) {
            $localizedContent[$defaultLocale] = $writtenSlice;
            $localeStatus[$defaultLocale] = $localeStatus[$locale];
        }

        $frontMatter = $content->getFrontMatter();
        $frontMatter['schemaVersion'] = 2;
        $frontMatter['defaultLocale'] = $defaultLocale;
        $frontMatter['localizedContent'] = $localizedContent;
        $frontMatter['localeStatus'] = $localeStatus;
        $frontMatter['updatedAt'] = AppTimezone::nowIso8601();
        if (!isset($frontMatter['createdAt'])) {
            $frontMatter['createdAt'] = $frontMatter['updatedAt'];
        }

        $content->setFrontMatter($frontMatter);
        $content->setSlug($slug);

        $this->syncFlatFieldsFromDefaultLocale($content, $defaultLocale, $localizedContent, $localeStatus, $locale);
    }

    /**
     * Upgrades a legacy (schema v1) document to persisted schema v2 front matter (Iteration 73 Phase 2d).
     */
    public function upgradeLegacyToSchemaV2(Content $content, string $defaultLocale): void
    {
        $defaultLocale = strtolower(trim($defaultLocale));

        /** @var array<string, mixed> $canonical */
        $canonical = $this->normalizer->normalize($content);
        /** @var array<string, array<string, mixed>> $localizedContent */
        $localizedContent = $canonical['localizedContent'];
        /** @var array<string, string> $localeStatus */
        $localeStatus = $canonical['localeStatus'];

        if (count($localizedContent) === 1) {
            $onlyKey = (string) array_key_first($localizedContent);
            if ($onlyKey !== $defaultLocale) {
                $localizedContent[$defaultLocale] = $localizedContent[$onlyKey];
                $localeStatus[$defaultLocale] = $localeStatus[$onlyKey] ?? 'draft';
                unset($localizedContent[$onlyKey], $localeStatus[$onlyKey]);
            }
        }

        $frontMatter = $content->getFrontMatter();
        $frontMatter['schemaVersion'] = 2;
        $frontMatter['defaultLocale'] = $defaultLocale;
        $frontMatter['localizedContent'] = $localizedContent;
        $frontMatter['localeStatus'] = $localeStatus;
        $frontMatter['updatedAt'] = AppTimezone::nowIso8601();
        if (!isset($frontMatter['createdAt'])) {
            $frontMatter['createdAt'] = $frontMatter['updatedAt'];
        }

        $content->setFrontMatter($frontMatter);
        $this->syncFlatFieldsFromDefaultLocale($content, $defaultLocale, $localizedContent, $localeStatus);
    }

    /**
     * Repairs legacy/schema-v2 documents where flat title/body were not synced (read path).
     * Never overwrites existing flat SSOT fields with empty locale slices.
     *
     * @return bool True when document was modified in memory (caller may persist).
     */
    public function hydrateFlatFieldsFromCanonical(Content $content): bool
    {
        $changed = $this->repairEmbeddedMetadataLeaks($content);

        /** @var array<string, mixed> $canonical */
        $canonical = $this->normalizer->normalize($content);
        /** @var array<string, array<string, mixed>> $localizedContent */
        $localizedContent = is_array($canonical['localizedContent'] ?? null)
            ? $canonical['localizedContent']
            : [];
        /** @var array<string, string> $localeStatus */
        $localeStatus = is_array($canonical['localeStatus'] ?? null) ? $canonical['localeStatus'] : [];

        $this->repairEmptyFlatFieldsFromCanonical(
            $content,
            (string) ($canonical['defaultLocale'] ?? 'sk'),
            $localizedContent,
            $localeStatus
        );

        $resolvedSlug = ContentSlug::resolveSlug(
            $content->getSlug(),
            $content->getTitle(),
            $content->getPath()
        );
        if ($resolvedSlug !== $content->getSlug()) {
            $content->setSlug($resolvedSlug);
            $changed = true;
        }

        return $changed;
    }

    /**
     * Syncs flat status and every locale row for bulk status changes (list actions).
     * Does not overwrite title/body/SEO slices.
     */
    public function applyBulkStatus(Content $content, string $status): void
    {
        /** @var array<string, mixed> $canonical */
        $canonical = $this->normalizer->normalize($content);
        $defaultLocale = (string) $canonical['defaultLocale'];
        /** @var array<string, array<string, mixed>> $localizedContent */
        $localizedContent = $canonical['localizedContent'];
        /** @var array<string, string> $localeStatus */
        $localeStatus = $canonical['localeStatus'];

        foreach (array_keys($localizedContent) as $locale) {
            $localeStatus[(string) $locale] = $status;
        }

        if ($localeStatus === []) {
            $localeStatus[$defaultLocale] = $status;
        }

        $frontMatter = $content->getFrontMatter();
        $schemaVersion = (int) ($frontMatter['schemaVersion'] ?? 1);
        if ($schemaVersion >= 2 || isset($frontMatter['localizedContent'])) {
            $frontMatter['schemaVersion'] = max(2, $schemaVersion);
            $frontMatter['defaultLocale'] = $defaultLocale;
            $frontMatter['localeStatus'] = $localeStatus;
            $frontMatter['updatedAt'] = AppTimezone::nowIso8601();
            $content->setFrontMatter($frontMatter);
        }

        $content->setStatus($status);
        $this->hydrateFlatFieldsFromCanonical($content);
    }

    public function applyLocaleStatus(Content $content, string $locale, string $status): void
    {
        $locale = strtolower(trim($locale));

        /** @var array<string, mixed> $canonical */
        $canonical = $this->normalizer->normalize($content);
        $defaultLocale = (string) $canonical['defaultLocale'];
        /** @var array<string, array<string, mixed>> $localizedContent */
        $localizedContent = $canonical['localizedContent'];
        /** @var array<string, string> $localeStatus */
        $localeStatus = $canonical['localeStatus'];

        if (!isset($localizedContent[$locale])) {
            $localizedContent[$locale] = [
                'title' => '',
                'body' => '',
                'seo' => [
                    'title' => '',
                    'description' => '',
                    'canonical' => '',
                    'ogImage' => '',
                    'noIndex' => false,
                ],
            ];
        }

        $localeStatus[$locale] = $status;

        $frontMatter = $content->getFrontMatter();
        $frontMatter['schemaVersion'] = max(2, (int) ($frontMatter['schemaVersion'] ?? 2));
        $frontMatter['defaultLocale'] = $defaultLocale;
        $frontMatter['localizedContent'] = $localizedContent;
        $frontMatter['localeStatus'] = $localeStatus;
        $frontMatter['updatedAt'] = AppTimezone::nowIso8601();
        $content->setFrontMatter($frontMatter);

        $this->syncFlatFieldsFromDefaultLocale($content, $defaultLocale, $localizedContent, $localeStatus);
    }

    /**
     * @param array<int|string, mixed> $data
     * @return array{title: string, description: string, canonical: string, ogImage: string, noIndex: bool}
     */
    private function buildSeoSlice(array $data): array
    {
        return [
            'title' => trim((string) ($data['seoTitle'] ?? '')),
            'description' => trim((string) ($data['seoDescription'] ?? $data['description'] ?? '')),
            'canonical' => trim((string) ($data['canonical'] ?? '')),
            'ogImage' => trim((string) ($data['ogImage'] ?? '')),
            'noIndex' => ($data['noIndex'] ?? false) === true,
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $localizedContent
     * @param array<string, string> $localeStatus
     */
    private function repairEmptyFlatFieldsFromCanonical(
        Content $content,
        string $defaultLocale,
        array $localizedContent,
        array $localeStatus,
    ): void {
        $resolved = $this->resolveDefaultLocaleSlice($defaultLocale, $localizedContent);
        $defaultSlice = $resolved['slice'];
        $resolvedLocale = $resolved['locale'];

        if (trim($content->getTitle()) === '' && trim((string) ($defaultSlice['title'] ?? '')) !== '') {
            $content->setTitle((string) $defaultSlice['title']);
        }

        if (trim($content->getContent()) === '' && trim((string) ($defaultSlice['body'] ?? '')) !== '') {
            $content->setContent((string) $defaultSlice['body']);
        }

        $currentStatus = trim($content->getStatus());
        if ($currentStatus === '' || $currentStatus === 'draft') {
            $resolvedStatus = (string) ($localeStatus[$resolvedLocale] ?? $localeStatus[$defaultLocale] ?? '');
            if ($resolvedStatus !== '' && $resolvedStatus !== 'draft') {
                $content->setStatus($resolvedStatus);
            }
        }

        /** @var array<string, mixed> $seo */
        $seo = is_array($defaultSlice['seo'] ?? null) ? $defaultSlice['seo'] : [];
        $frontMatter = $content->getFrontMatter();

        if (trim((string) ($frontMatter['seoTitle'] ?? '')) === '' && trim((string) ($seo['title'] ?? '')) !== '') {
            $frontMatter['seoTitle'] = (string) $seo['title'];
        }
        if (trim((string) ($frontMatter['seoDescription'] ?? '')) === '' && trim((string) ($seo['description'] ?? '')) !== '') {
            $frontMatter['seoDescription'] = (string) $seo['description'];
        }
        if (trim((string) ($frontMatter['canonical'] ?? '')) === '' && trim((string) ($seo['canonical'] ?? '')) !== '') {
            $frontMatter['canonical'] = (string) $seo['canonical'];
        }
        if (trim((string) ($frontMatter['seoImage'] ?? '')) === '' && trim((string) ($seo['ogImage'] ?? '')) !== '') {
            $frontMatter['seoImage'] = (string) $seo['ogImage'];
        }
        if (!($frontMatter['noIndex'] ?? false) && ($seo['noIndex'] ?? false) === true) {
            $frontMatter['noIndex'] = true;
        }

        $content->setFrontMatter($frontMatter);
    }

    private function repairEmbeddedMetadataLeaks(Content $content): bool
    {
        $changed = false;

        $cleanBody = ContentBodySanitizer::stripEmbeddedMetadataLeak($content->getContent());
        if ($cleanBody !== $content->getContent()) {
            $content->setContent($cleanBody);
            $changed = true;
        }

        $frontMatter = $content->getFrontMatter();
        if (!is_array($frontMatter['localizedContent'] ?? null)) {
            return $changed;
        }

        /** @var array<string, mixed> $localized */
        $localized = $frontMatter['localizedContent'];
        $defaultLocale = (string) ($frontMatter['defaultLocale'] ?? 'sk');
        $flatBody = $content->getContent();
        $flatTitle = trim($content->getTitle());

        foreach ($localized as $locale => $slice) {
            if (!is_array($slice)) {
                continue;
            }

            $localeKey = (string) $locale;
            $sliceBody = (string) ($slice['body'] ?? '');
            $repairedBody = ContentBodySanitizer::stripEmbeddedMetadataLeak($sliceBody);
            if ($repairedBody !== $sliceBody) {
                $localized[$localeKey] = $slice;
                $localized[$localeKey]['body'] = $repairedBody;
                $changed = true;
            }
        }

        $defaultSlice = $localized[$defaultLocale] ?? null;
        if (is_array($defaultSlice)) {
            $defaultBody = trim((string) ($localized[$defaultLocale]['body'] ?? ''));
            if ($defaultBody === '' && trim($flatBody) !== '') {
                $localized[$defaultLocale]['body'] = $flatBody;
                $changed = true;
            } elseif (
                ContentBodySanitizer::looksLikeMetadataLeak($defaultBody)
                && trim($flatBody) !== ''
                && !ContentBodySanitizer::looksLikeMetadataLeak($flatBody)
            ) {
                $localized[$defaultLocale]['body'] = $flatBody;
                $changed = true;
            }

            if (trim((string) ($localized[$defaultLocale]['title'] ?? '')) === '' && $flatTitle !== '') {
                $localized[$defaultLocale]['title'] = $flatTitle;
                $changed = true;
            }
        }

        if ($changed) {
            $frontMatter['localizedContent'] = $localized;
            $content->setFrontMatter($frontMatter);
        }

        return $changed;
    }

    /**
     * @param array<string, array<string, mixed>> $localizedContent
     * @return array{slice: array<string, mixed>, locale: string}
     */
    private function resolveDefaultLocaleSlice(string $defaultLocale, array $localizedContent): array
    {
        $resolvedLocale = $defaultLocale;
        $defaultSlice = $localizedContent[$defaultLocale] ?? null;

        if ($this->sliceIsEmpty($defaultSlice)) {
            $defaultSlice = null;
            foreach ($localizedContent as $localeCode => $slice) {
                if (!$this->sliceIsEmpty($slice)) {
                    $defaultSlice = $slice;
                    $resolvedLocale = (string) $localeCode;
                    break;
                }
            }
        }

        if ($defaultSlice === null) {
            $defaultSlice = [
                'title' => '',
                'body' => '',
                'seo' => [
                    'title' => '',
                    'description' => '',
                    'canonical' => '',
                    'ogImage' => '',
                    'noIndex' => false,
                ],
            ];
        }

        return ['slice' => $defaultSlice, 'locale' => $resolvedLocale];
    }

    /**
     * @param array<string, array<string, mixed>> $localizedContent
     * @param array<string, string> $localeStatus
     * @param string|null $writtenLocale Locale code from the current write, if any.
     */
    private function syncFlatFieldsFromDefaultLocale(
        Content $content,
        string $defaultLocale,
        array $localizedContent,
        array $localeStatus,
        ?string $writtenLocale = null,
    ): void {
        $resolved = $this->resolveDefaultLocaleSlice($defaultLocale, $localizedContent);
        $defaultSlice = $resolved['slice'];
        $resolvedLocale = $resolved['locale'];

        $sliceTitle = trim((string) ($defaultSlice['title'] ?? ''));
        $sliceBody = trim((string) ($defaultSlice['body'] ?? ''));
        $writingDefault = $writtenLocale !== null && $writtenLocale === $defaultLocale;

        if ($writingDefault || $writtenLocale === null) {
            if ($sliceTitle !== '' || trim($content->getTitle()) === '') {
                $content->setTitle((string) ($defaultSlice['title'] ?? ''));
            }
            if ($sliceBody !== '' || trim($content->getContent()) === '') {
                $content->setContent((string) ($defaultSlice['body'] ?? ''));
            }
        } else {
            if (trim($content->getTitle()) === '' && $sliceTitle !== '') {
                $content->setTitle($sliceTitle);
            }
            if (trim($content->getContent()) === '' && $sliceBody !== '') {
                $content->setContent($sliceBody);
            }
        }

        $content->setStatus((string) ($localeStatus[$defaultLocale] ?? $localeStatus[$resolvedLocale] ?? 'draft'));

        $frontMatter = $content->getFrontMatter();
        /** @var array<string, mixed> $seo */
        $seo = is_array($defaultSlice['seo'] ?? null) ? $defaultSlice['seo'] : [];

        if (trim((string) ($frontMatter['seoTitle'] ?? '')) === '' || trim((string) ($seo['title'] ?? '')) !== '') {
            $frontMatter['seoTitle'] = (string) ($seo['title'] ?? '');
        }
        if (trim((string) ($frontMatter['seoDescription'] ?? '')) === '' || trim((string) ($seo['description'] ?? '')) !== '') {
            $frontMatter['seoDescription'] = (string) ($seo['description'] ?? '');
        }
        if (trim((string) ($frontMatter['canonical'] ?? '')) === '' || trim((string) ($seo['canonical'] ?? '')) !== '') {
            $frontMatter['canonical'] = (string) ($seo['canonical'] ?? '');
        }
        if (trim((string) ($frontMatter['seoImage'] ?? '')) === '' || trim((string) ($seo['ogImage'] ?? '')) !== '') {
            $frontMatter['seoImage'] = (string) ($seo['ogImage'] ?? '');
        }
        if (!($frontMatter['noIndex'] ?? false) && ($seo['noIndex'] ?? false) === true) {
            $frontMatter['noIndex'] = true;
        }
        $content->setFrontMatter($frontMatter);
    }

    /**
     * @param array<string, mixed>|null $slice
     */
    private function sliceIsEmpty(?array $slice): bool
    {
        if ($slice === null) {
            return true;
        }

        $title = trim((string) ($slice['title'] ?? ''));
        $body = trim((string) ($slice['body'] ?? ''));

        return $title === '' && $body === '';
    }
}
