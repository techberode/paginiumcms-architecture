<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Content;

/**
 * Result of locale resolution for a content resource (Iteration 73).
 */
final class LocaleResolution
{
    /**
     * @param list<string> $availableLocales
     */
    public function __construct(
        public readonly ?string $requested,
        public readonly string $resolved,
        public readonly bool $fallback,
        public readonly array $availableLocales,
    ) {
    }

    public function cacheKeySuffix(): string
    {
        $requested = $this->requested ?? '_default';

        return $requested . '>' . $this->resolved . ($this->fallback ? ':fb' : '');
    }
}
