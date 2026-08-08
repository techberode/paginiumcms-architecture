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

        $this->syncFlatFieldsFromDefaultLocale($content, $defaultLocale, $localizedContent, $localeStatus);
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
    private function syncFlatFieldsFromDefaultLocale(
        Content $content,
        string $defaultLocale,
        array $localizedContent,
        array $localeStatus,
    ): void {
        $defaultSlice = $localizedContent[$defaultLocale] ?? reset($localizedContent) ?: [
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

        $content->setTitle((string) ($defaultSlice['title'] ?? ''));
        $content->setContent((string) ($defaultSlice['body'] ?? ''));
        $content->setStatus((string) ($localeStatus[$defaultLocale] ?? 'draft'));

        $frontMatter = $content->getFrontMatter();
        /** @var array<string, mixed> $seo */
        $seo = is_array($defaultSlice['seo'] ?? null) ? $defaultSlice['seo'] : [];
        $frontMatter['seoTitle'] = (string) ($seo['title'] ?? '');
        $frontMatter['seoDescription'] = (string) ($seo['description'] ?? '');
        $frontMatter['canonical'] = (string) ($seo['canonical'] ?? '');
        $frontMatter['seoImage'] = (string) ($seo['ogImage'] ?? '');
        $frontMatter['noIndex'] = ($seo['noIndex'] ?? false) === true;
        $content->setFrontMatter($frontMatter);
    }
}
