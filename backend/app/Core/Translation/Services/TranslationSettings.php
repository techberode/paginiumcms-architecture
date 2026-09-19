<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Translation\Services;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Reads the translation settings group (It.76 / It.77).
 */
final class TranslationSettings
{
    /** @var list<string> */
    public const PROVIDERS = ['none', 'libretranslate', 'deepl', 'google'];

    public function __construct(
        private SettingsRepositoryInterface $settings,
    ) {
    }

    public function isEnabled(): bool
    {
        $group = $this->settings->group('translation');

        return (bool) ($group['enabled'] ?? false);
    }

    public function provider(): string
    {
        return $this->normalizeProvider((string) ($this->settings->group('translation')['provider'] ?? 'none'));
    }

    public function isActive(): bool
    {
        return $this->isEnabled() && $this->provider() !== 'none';
    }

    public function baseUrl(): string
    {
        $group = $this->settings->group('translation');

        return rtrim(trim((string) ($group['baseUrl'] ?? '')), '/');
    }

    public function apiKey(): string
    {
        $group = $this->settings->group('translation');

        return trim((string) ($group['apiKey'] ?? ''));
    }

    public function deeplApiKey(): string
    {
        $group = $this->settings->group('translation');

        return trim((string) ($group['deeplApiKey'] ?? ''));
    }

    public function googleApiKey(): string
    {
        $group = $this->settings->group('translation');

        return trim((string) ($group['googleApiKey'] ?? ''));
    }

    public function googleProjectId(): string
    {
        $group = $this->settings->group('translation');

        return trim((string) ($group['googleProjectId'] ?? ''));
    }

    public function fallbackEnabled(): bool
    {
        $group = $this->settings->group('translation');

        return (bool) ($group['fallbackEnabled'] ?? false);
    }

    public function fallbackProvider(): string
    {
        $fallback = $this->normalizeProvider((string) ($this->settings->group('translation')['fallbackProvider'] ?? 'none'));
        if (!$this->fallbackEnabled() || $fallback === 'none' || $fallback === $this->provider()) {
            return 'none';
        }

        return $fallback;
    }

    public function dailyCharLimit(): int
    {
        $group = $this->settings->group('translation');

        return max(0, (int) ($group['dailyCharLimit'] ?? 0));
    }

    public function overwriteExisting(): bool
    {
        $group = $this->settings->group('translation');

        return (bool) ($group['overwriteExisting'] ?? false);
    }

    public function timeoutSeconds(): int
    {
        $group = $this->settings->group('translation');

        return max(3, min(60, (int) ($group['timeoutSeconds'] ?? 15)));
    }

    private function normalizeProvider(string $provider): string
    {
        $provider = strtolower(trim($provider));

        return in_array($provider, self::PROVIDERS, true) ? $provider : 'none';
    }
}
