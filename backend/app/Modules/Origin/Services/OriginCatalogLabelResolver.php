<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Services;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Resolves origin.* titleKey strings from backend/lang/{locale}/origin.php (It.82e).
 * Ensures Origin Panel labels render on production even when the admin JS bundle lags the manifest.
 */
final class OriginCatalogLabelResolver
{
    /** @var array<string, array<string, mixed>> */
    private array $cache = [];

    public function __construct(
        private SettingsRepositoryInterface $settings,
    ) {
    }

    public function resolve(string $titleKey): string
    {
        if ($titleKey === '' || !str_starts_with($titleKey, 'origin.')) {
            return $titleKey;
        }

        $path = substr($titleKey, strlen('origin.'));
        $label = $this->resolvePath($this->messages($this->locale()), $path);

        return $label ?? $titleKey;
    }

    private function locale(): string
    {
        $language = (string) ($this->settings->get('general.language') ?? 'sk');

        return in_array($language, ['sk', 'en'], true) ? $language : 'sk';
    }

    /**
     * @return array<string, mixed>
     */
    private function messages(string $locale): array
    {
        if (isset($this->cache[$locale])) {
            return $this->cache[$locale];
        }

        $path = __DIR__ . '/../../../../lang/' . $locale . '/origin.php';
        if (!is_readable($path)) {
            $path = __DIR__ . '/../../../../lang/sk/origin.php';
        }

        $loaded = is_readable($path) ? require $path : [];

        $this->cache[$locale] = is_array($loaded) ? $loaded : [];

        return $this->cache[$locale];
    }

    /**
     * @param array<string, mixed> $tree
     */
    private function resolvePath(array $tree, string $path): ?string
    {
        $current = $tree;
        foreach (explode('.', $path) as $segment) {
            if ($segment === '' || !is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }
            $current = $current[$segment];
        }

        return is_string($current) ? $current : null;
    }
}
