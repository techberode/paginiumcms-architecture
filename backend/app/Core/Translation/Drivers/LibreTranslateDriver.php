<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Translation\Drivers;

use PaginiumCMS\Core\Translation\Contracts\TranslationHttpTransport;
use PaginiumCMS\Core\Translation\Contracts\TranslationProviderInterface;
use PaginiumCMS\Core\Translation\Exception\TranslationException;
use PaginiumCMS\Core\Translation\Services\TranslationSettings;

/**
 * LibreTranslate-compatible REST driver (It.76).
 */
final class LibreTranslateDriver implements TranslationProviderInterface
{
    public function __construct(
        private TranslationSettings $settings,
        private TranslationHttpTransport $transport,
    ) {
    }

    public function id(): string
    {
        return 'libretranslate';
    }

    public function health(): array
    {
        $base = $this->settings->baseUrl();
        if ($base === '') {
            return ['ok' => false, 'error' => 'LibreTranslate URL is not configured'];
        }

        try {
            $this->transport->request('GET', $base . '/languages', null, $this->settings->timeoutSeconds());

            return ['ok' => true];
        } catch (TranslationException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function translate(string $text, string $sourceLocale, string $targetLocale): array
    {
        $base = $this->settings->baseUrl();
        if ($base === '') {
            throw new TranslationException('LibreTranslate URL is not configured', 503, 'PROVIDER_UNAVAILABLE');
        }

        $payload = [
            'q' => $text,
            'source' => $sourceLocale,
            'target' => $targetLocale,
            'format' => 'text',
        ];
        $apiKey = $this->settings->apiKey();
        if ($apiKey !== '') {
            $payload['api_key'] = $apiKey;
        }

        $response = $this->transport->request(
            'POST',
            $base . '/translate',
            $payload,
            $this->settings->timeoutSeconds()
        );

        $translated = $response['translatedText'] ?? null;
        if (!is_string($translated) || $translated === '') {
            throw new TranslationException('Invalid provider response', 502, 'INVALID_RESPONSE');
        }

        return [
            'text' => $translated,
            'characters' => mb_strlen($text),
        ];
    }
}
