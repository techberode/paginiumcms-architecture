<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Content;

use PaginiumCMS\Core\I18n\Services\SupportedLocalesRegistry;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Deterministic locale resolution for public content (Iteration 73).
 *
 * Order: ?locale= → Accept-Language (when enabled) → resource default → site default → fallback policy.
 */
final class LocaleResolver
{
    public function __construct(
        private SupportedLocalesRegistry $locales,
        private SettingsRepositoryInterface $settings,
    ) {
    }

    /**
     * @param list<string> $availableLocales Locales present on the resource (normalized codes).
     */
    public function resolveForRequest(
        ServerRequestInterface $request,
        array $availableLocales,
        ?string $resourceDefaultLocale = null,
    ): LocaleResolution {
        $availableLocales = $this->normalizeLocaleList($availableLocales);
        $siteDefault = $this->siteDefaultLocale();
        $resourceDefault = $this->normalizeLocale($resourceDefaultLocale ?? '') ?? $siteDefault;

        $requested = $this->requestedFromQuery($request)
            ?? ($this->negotiationEnabled() ? $this->requestedFromAcceptLanguage($request) : null);

        if ($requested !== null && in_array($requested, $availableLocales, true)) {
            return new LocaleResolution(
                $requested,
                $requested,
                false,
                $availableLocales
            );
        }

        if ($resourceDefault !== '' && in_array($resourceDefault, $availableLocales, true)) {
            return new LocaleResolution(
                $requested,
                $resourceDefault,
                $this->usedFallback($requested, $resourceDefault),
                $availableLocales
            );
        }

        $publishedFallback = $this->firstAvailableLocale($availableLocales);
        if ($publishedFallback !== null && $this->fallbackEnabled()) {
            return new LocaleResolution(
                $requested,
                $publishedFallback,
                true,
                $availableLocales
            );
        }

        return new LocaleResolution(
            $requested,
            $resourceDefault !== '' ? $resourceDefault : $siteDefault,
            true,
            $availableLocales
        );
    }

    public function requestedFromQuery(ServerRequestInterface $request): ?string
    {
        $params = $request->getQueryParams();
        $locale = $this->normalizeLocale((string) ($params['locale'] ?? ''));

        return $locale !== null && $this->locales->isSupported($locale) ? $locale : null;
    }

    private function requestedFromAcceptLanguage(ServerRequestInterface $request): ?string
    {
        $header = $request->getHeaderLine('Accept-Language');
        if ($header === '') {
            return null;
        }

        foreach (explode(',', $header) as $part) {
            $tag = strtolower(trim(explode(';', $part)[0]));
            $short = substr($tag, 0, 2);
            if ($this->locales->isSupported($short)) {
                return $short;
            }
        }

        return null;
    }

    private function siteDefaultLocale(): string
    {
        $configured = $this->normalizeLocale((string) $this->settings->get('general.language', 'sk'));

        return $configured ?? 'sk';
    }

    private function fallbackEnabled(): bool
    {
        return ($this->settings->group('content')['localeFallbackEnabled'] ?? true) === true;
    }

    private function negotiationEnabled(): bool
    {
        return ($this->settings->group('content')['localeNegotiationEnabled'] ?? true) === true;
    }

    /**
     * @param list<string> $availableLocales
     */
    private function firstAvailableLocale(array $availableLocales): ?string
    {
        return $availableLocales[0] ?? null;
    }

    private function normalizeLocale(string $locale): ?string
    {
        $locale = strtolower(trim($locale));

        return $locale !== '' && preg_match('/^[a-z]{2}$/', $locale) === 1 ? $locale : null;
    }

    /**
     * @param list<string> $locales
     * @return list<string>
     */
    private function normalizeLocaleList(array $locales): array
    {
        $normalized = [];
        foreach ($locales as $locale) {
            $code = $this->normalizeLocale($locale);
            if ($code !== null && $this->locales->isSupported($code) && !in_array($code, $normalized, true)) {
                $normalized[] = $code;
            }
        }

        return $normalized;
    }

    private function usedFallback(?string $requested, string $resolved): bool
    {
        if ($requested === null) {
            return false;
        }

        return $requested !== $resolved;
    }
}
