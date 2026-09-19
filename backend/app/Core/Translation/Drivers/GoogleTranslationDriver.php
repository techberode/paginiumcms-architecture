<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Translation\Drivers;

use PaginiumCMS\Core\Translation\Contracts\TranslationHttpTransport;
use PaginiumCMS\Core\Translation\Contracts\TranslationProviderInterface;
use PaginiumCMS\Core\Translation\Exception\TranslationException;
use PaginiumCMS\Core\Translation\Services\TranslationCredentialResolver;
use PaginiumCMS\Core\Translation\Services\TranslationFixedHostPolicy;
use PaginiumCMS\Core\Translation\Services\TranslationSettings;

/**
 * Google Cloud Translation v2 API-key driver with a fixed host (It.77).
 */
final class GoogleTranslationDriver implements TranslationProviderInterface
{
    private const TEST_PHRASE = 'Paginium';

    private const ENDPOINT = 'https://translation.googleapis.com/language/translate/v2';

    public function __construct(
        private TranslationSettings $settings,
        private TranslationHttpTransport $transport,
        private TranslationCredentialResolver $credentials,
        private TranslationFixedHostPolicy $hosts,
    ) {
    }

    public function id(): string
    {
        return 'google';
    }

    public function health(): array
    {
        try {
            $this->translate(self::TEST_PHRASE, 'en', 'sk');

            return ['ok' => true];
        } catch (TranslationException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function translate(string $text, string $sourceLocale, string $targetLocale): array
    {
        $apiKey = $this->credentials->apiKeyFor('google');
        if ($apiKey === '') {
            throw new TranslationException('Google Translation API key is not configured', 503, 'PROVIDER_UNAVAILABLE');
        }

        $this->hosts->assertProviderUrl('google', self::ENDPOINT);

        $response = $this->transport->request(
            'POST',
            self::ENDPOINT . '?key=' . rawurlencode($apiKey),
            [
                'q' => $text,
                'source' => strtolower($sourceLocale),
                'target' => strtolower($targetLocale),
                'format' => 'text',
            ],
            $this->settings->timeoutSeconds()
        );

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $translations = is_array($data['translations'] ?? null) ? $data['translations'] : [];
        $first = is_array($translations[0] ?? null) ? $translations[0] : [];
        $translated = $first['translatedText'] ?? null;
        if (!is_string($translated) || $translated === '') {
            throw new TranslationException('Invalid provider response', 502, 'INVALID_RESPONSE');
        }

        return [
            'text' => $translated,
            'characters' => mb_strlen($text),
        ];
    }
}
