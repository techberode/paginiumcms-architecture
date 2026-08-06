<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Content;

/**
 * Applies a resolved locale slice onto a serialized content API payload (Iteration 73).
 */
final class LocalizedContentApplicator
{
    /**
     * @param array<string, mixed> $payload Serialized content from ContentController.
     * @param array<string, mixed> $canonical Output of LocalizedContentNormalizer::normalize().
     * @return array<string, mixed>
     */
    public function apply(
        array $payload,
        array $canonical,
        LocaleResolution $resolution,
    ): array {
        /** @var array<string, array<string, mixed>> $localized */
        $localized = $canonical['localizedContent'];
        $slice = $localized[$resolution->resolved] ?? null;

        if (!is_array($slice)) {
            $payload['_locale'] = $this->localeMeta($canonical, $resolution, false);

            return $payload;
        }

        $payload['title'] = (string) ($slice['title'] ?? $payload['title'] ?? '');
        $payload['content'] = (string) ($slice['body'] ?? $payload['content'] ?? '');

        /** @var array<string, mixed> $seo */
        $seo = is_array($slice['seo'] ?? null) ? $slice['seo'] : [];
        $payload['seoTitle'] = (string) ($seo['title'] ?? $payload['seoTitle'] ?? '');
        $payload['seoDescription'] = (string) ($seo['description'] ?? $payload['seoDescription'] ?? '');
        $payload['canonical'] = (string) ($seo['canonical'] ?? $payload['canonical'] ?? '');
        $payload['ogImage'] = (string) ($seo['ogImage'] ?? $payload['ogImage'] ?? '');
        $payload['noIndex'] = ($seo['noIndex'] ?? $payload['noIndex'] ?? false) === true;

        /** @var array<string, string> $localeStatus */
        $localeStatus = is_array($canonical['localeStatus'] ?? null) ? $canonical['localeStatus'] : [];
        if (isset($localeStatus[$resolution->resolved])) {
            $payload['status'] = $localeStatus[$resolution->resolved];
        }

        $payload['_locale'] = $this->localeMeta($canonical, $resolution, true);
        $payload['schemaVersion'] = (int) ($canonical['schemaVersion'] ?? 1);
        $payload['defaultLocale'] = (string) ($canonical['defaultLocale'] ?? 'sk');

        return $payload;
    }

    /**
     * @param array<string, mixed> $canonical
     * @return array<string, mixed>
     */
    private function localeMeta(
        array $canonical,
        LocaleResolution $resolution,
        bool $sliceFound,
    ): array {
        return [
            'requested' => $resolution->requested,
            'resolved' => $resolution->resolved,
            'fallback' => $resolution->fallback,
            'available' => $resolution->availableLocales,
            'defaultLocale' => (string) ($canonical['defaultLocale'] ?? 'sk'),
            'sliceFound' => $sliceFound,
        ];
    }
}
