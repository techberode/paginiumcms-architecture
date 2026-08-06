<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Content;

use PaginiumCMS\Core\FlatFile\Models\Content;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Normalizes legacy and schema v2 content into a canonical multi-locale read model (Iteration 73).
 * Does not mutate on-disk documents.
 */
final class LocalizedContentNormalizer
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function normalize(Content $content): array
    {
        /** @var array<string, mixed> $frontMatter */
        $frontMatter = $content->getFrontMatter();
        $schemaVersion = (int) ($frontMatter['schemaVersion'] ?? 1);

        if ($schemaVersion >= 2 && is_array($frontMatter['localizedContent'] ?? null)) {
            return $this->normalizeV2($frontMatter, $content);
        }

        return $this->normalizeLegacy($content, $frontMatter);
    }

    /**
     * @param array<string, mixed> $frontMatter
     * @return array<string, mixed>
     */
    private function normalizeLegacy(Content $content, array $frontMatter): array
    {
        $defaultLocale = $this->normalizeLocaleCode((string) ($frontMatter['defaultLocale'] ?? ''))
            ?? $this->siteDefaultLocale();
        $status = (string) ($content->getStatus() ?: 'draft');

        return [
            'schemaVersion' => 1,
            'defaultLocale' => $defaultLocale,
            'localizedContent' => [
                $defaultLocale => $this->buildLocaleSlice($content, $frontMatter),
            ],
            'localeStatus' => [
                $defaultLocale => $status,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $frontMatter
     * @return array<string, mixed>
     */
    private function normalizeV2(array $frontMatter, Content $content): array
    {
        $defaultLocale = $this->normalizeLocaleCode((string) ($frontMatter['defaultLocale'] ?? ''))
            ?? $this->siteDefaultLocale();
        /** @var array<string, mixed> $rawLocales */
        $rawLocales = $frontMatter['localizedContent'];
        $localizedContent = [];
        $localeStatus = is_array($frontMatter['localeStatus'] ?? null) ? $frontMatter['localeStatus'] : [];

        foreach ($rawLocales as $locale => $slice) {
            if (!is_array($slice)) {
                continue;
            }

            $code = $this->normalizeLocaleCode((string) $locale);
            if ($code === null) {
                continue;
            }

            $localizedContent[$code] = [
                'title' => (string) ($slice['title'] ?? $content->getTitle()),
                'body' => (string) ($slice['body'] ?? $slice['content'] ?? ''),
                'seo' => $this->normalizeSeoSlice(is_array($slice['seo'] ?? null) ? $slice['seo'] : [], $frontMatter),
            ];
            $localeStatus[$code] = (string) ($localeStatus[$code] ?? $content->getStatus() ?: 'draft');
        }

        if ($localizedContent === []) {
            return $this->normalizeLegacy($content, $frontMatter);
        }

        return [
            'schemaVersion' => max(2, (int) ($frontMatter['schemaVersion'] ?? 2)),
            'defaultLocale' => $defaultLocale,
            'localizedContent' => $localizedContent,
            'localeStatus' => $localeStatus,
        ];
    }

    /**
     * @param array<string, mixed> $frontMatter
     * @return array{title: string, body: string, seo: array{title: string, description: string, canonical: string, ogImage: string, noIndex: bool}}
     */
    private function buildLocaleSlice(Content $content, array $frontMatter): array
    {
        return [
            'title' => $content->getTitle(),
            'body' => $content->getContent(),
            'seo' => $this->normalizeSeoSlice([], $frontMatter),
        ];
    }

    /**
     * @param array<string, mixed> $seo
     * @param array<string, mixed> $frontMatter
     * @return array{title: string, description: string, canonical: string, ogImage: string, noIndex: bool}
     */
    private function normalizeSeoSlice(array $seo, array $frontMatter): array
    {
        return [
            'title' => (string) ($seo['title'] ?? $frontMatter['seoTitle'] ?? $frontMatter['metaTitle'] ?? ''),
            'description' => (string) ($seo['description'] ?? $frontMatter['seoDescription'] ?? $frontMatter['description'] ?? ''),
            'canonical' => (string) ($seo['canonical'] ?? $frontMatter['canonical'] ?? ''),
            'ogImage' => (string) ($seo['ogImage'] ?? $frontMatter['seoImage'] ?? $frontMatter['ogImage'] ?? ''),
            'noIndex' => ($seo['noIndex'] ?? $frontMatter['noIndex'] ?? $frontMatter['noindex'] ?? false) === true,
        ];
    }

    private function siteDefaultLocale(): string
    {
        return $this->normalizeLocaleCode((string) $this->settings->get('general.language', 'sk')) ?? 'sk';
    }

    private function normalizeLocaleCode(string $locale): ?string
    {
        $locale = strtolower(trim($locale));

        return $locale !== '' && preg_match('/^[a-z]{2}$/', $locale) === 1 ? $locale : null;
    }
}
