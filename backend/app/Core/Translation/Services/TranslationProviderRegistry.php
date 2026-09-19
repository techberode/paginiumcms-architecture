<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Translation\Services;

use PaginiumCMS\Core\Translation\Contracts\TranslationHttpTransport;
use PaginiumCMS\Core\Translation\Contracts\TranslationProviderInterface;
use PaginiumCMS\Core\Translation\Drivers\DeepLTranslationDriver;
use PaginiumCMS\Core\Translation\Drivers\GoogleTranslationDriver;
use PaginiumCMS\Core\Translation\Drivers\LibreTranslateDriver;
use PaginiumCMS\Core\Translation\Drivers\NoneTranslationDriver;

/**
 * Allow-listed translation providers (It.76 / It.77). No dynamic class names.
 */
final class TranslationProviderRegistry
{
    public function __construct(
        private TranslationSettings $settings,
        private TranslationHttpTransport $transport,
        private TranslationCredentialResolver $credentials,
        private TranslationFixedHostPolicy $hosts,
    ) {
    }

    public function resolve(?string $providerId = null): TranslationProviderInterface
    {
        if (!$this->settings->isEnabled()) {
            return new NoneTranslationDriver();
        }

        $id = $providerId ?? $this->settings->provider();

        return match ($id) {
            'libretranslate' => new LibreTranslateDriver($this->settings, $this->transport),
            'deepl' => new DeepLTranslationDriver($this->settings, $this->transport, $this->credentials, $this->hosts),
            'google' => new GoogleTranslationDriver($this->settings, $this->transport, $this->credentials, $this->hosts),
            default => new NoneTranslationDriver(),
        };
    }
}
