<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Translation\Services;

use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;
use PaginiumCMS\Core\Translation\Contracts\TranslationHttpTransport;
use PaginiumCMS\Core\Translation\Exception\TranslationException;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * curl client with SSRF guard for LibreTranslate-compatible endpoints (It.76).
 */
final class TranslationHttpClient implements TranslationHttpTransport
{
    public function __construct(
        private ?OutboundUrlGuard $urlGuard = null,
    ) {
    }

    /**
     * @param array<string, string> $extraHeaders
     */
    public function request(string $method, string $url, ?array $body, int $timeoutSeconds, array $extraHeaders = []): array
    {
        try {
            ($this->urlGuard ?? OutboundUrlGuard::fromEnv())->assertAllowed($url);
        } catch (RuntimeException) {
            throw new TranslationException('Translation provider URL is not allowed', 422, 'SSRF_BLOCKED');
        }

        $handle = curl_init($url);
        if ($handle === false) {
            throw new TranslationException('Translation provider request failed', 503, 'PROVIDER_UNAVAILABLE');
        }

        $verb = strtoupper($method);
        if ($verb !== 'GET' && $verb !== 'POST') {
            throw new TranslationException('Unsupported HTTP method', 422, 'INVALID');
        }

        $headers = [
            'Accept: application/json',
            'User-Agent: PaginiumCMS-Translation',
        ];
        foreach ($extraHeaders as $name => $value) {
            $safe = strtolower($name);
            if ($safe !== 'authorization' && $safe !== 'content-type') {
                continue;
            }
            $headers[] = $name . ': ' . $value;
        }
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => max(3, min(60, $timeoutSeconds)),
            CURLOPT_CUSTOMREQUEST => $verb,
            CURLOPT_HTTPHEADER => $headers,
        ];
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            $options[CURLOPT_HTTPHEADER] = $headers;
            $options[CURLOPT_POSTFIELDS] = JsonHelper::encode($body);
        }

        curl_setopt_array($handle, $options);
        $raw = curl_exec($handle);
        $http = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        if (!is_string($raw)) {
            throw new TranslationException(
                'Translation provider unavailable',
                503,
                'PROVIDER_UNAVAILABLE'
            );
        }

        if ($http >= 400) {
            throw (new TranslationErrorMapper())->fromHttp($http);
        }

        if ($raw === '') {
            return [];
        }

        try {
            return $this->stringKeyed(JsonHelper::decode($raw));
        } catch (\Throwable) {
            throw new TranslationException('Invalid provider response', 502, 'INVALID_RESPONSE');
        }
    }

    /**
     * @param array<int|string, mixed> $decoded
     * @return array<string, mixed>
     */
    private function stringKeyed(array $decoded): array
    {
        $out = [];
        foreach ($decoded as $key => $value) {
            if (is_string($key)) {
                $out[$key] = $value;
            }
        }

        return $out;
    }
}
