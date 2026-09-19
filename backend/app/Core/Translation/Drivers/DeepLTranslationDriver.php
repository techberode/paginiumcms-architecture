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
 * DeepL REST driver with fixed vendor hosts (It.77).
 */
final class DeepLTranslationDriver implements TranslationProviderInterface
{
    private const TEST_PHRASE = 'Paginium';

    public function __construct(
        private TranslationSettings $settings,
        private TranslationHttpTransport $transport,
        private TranslationCredentialResolver $credentials,
        private TranslationFixedHostPolicy $hosts,
    ) {
    }

    public function id(): string
    {
        return 'deepl';
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
        $apiKey = $this->credentials->apiKeyFor('deepl');
        if ($apiKey === '') {
            throw new TranslationException('DeepL API key is not configured', 503, 'PROVIDER_UNAVAILABLE');
        }

        $url = $this->endpointUrl($apiKey);
        $this->hosts->assertProviderUrl('deepl', $url);

        $response = $this->transport->request(
            'POST',
            $url,
            [
                'text' => [$text],
                'source_lang' => strtoupper($sourceLocale),
                'target_lang' => strtoupper($targetLocale),
            ],
            $this->settings->timeoutSeconds(),
            ['Authorization' => 'DeepL-Auth-Key ' . $apiKey]
        );

        $translations = $response['translations'] ?? null;
        $first = is_array($translations) ? ($translations[0] ?? null) : null;
        $translated = is_array($first) ? ($first['text'] ?? null) : null;
        if (!is_string($translated) || $translated === '') {
            throw new TranslationException('Invalid provider response', 502, 'INVALID_RESPONSE');
        }

        return [
            'text' => $translated,
            'characters' => mb_strlen($text),
        ];
    }

    private function endpointUrl(string $apiKey): string
    {
        $host = str_ends_with($apiKey, ':fx') ? 'api-free.deepl.com' : 'api.deepl.com';

        return 'https://' . $host . '/v2/translate';
    }
}
