<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Translation\Services;

/**
 * Reads a provider credential only at request time (It.77). Never log the return value.
 */
final class TranslationCredentialResolver
{
    public function __construct(
        private TranslationSettings $settings,
    ) {
    }

    public function apiKeyFor(string $provider): string
    {
        return match ($provider) {
            'libretranslate' => $this->settings->apiKey(),
            'deepl' => $this->settings->deeplApiKey(),
            'google' => $this->settings->googleApiKey(),
            default => '',
        };
    }
}
