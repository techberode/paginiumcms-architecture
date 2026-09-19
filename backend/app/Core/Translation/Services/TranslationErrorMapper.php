<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Translation\Services;

use PaginiumCMS\Core\Translation\Exception\TranslationException;

/**
 * Maps provider failures to stable domain codes without vendor payloads (It.77).
 */
final class TranslationErrorMapper
{
    public function fromHttp(int $status): TranslationException
    {
        return match (true) {
            $status === 429 => new TranslationException('Translation provider rate limited', 429, 'RATE_LIMITED'),
            $status === 401, $status === 403 => new TranslationException('Translation provider authentication failed', 502, 'AUTH_FAILED'),
            default => new TranslationException('Translation provider unavailable', 503, 'PROVIDER_UNAVAILABLE'),
        };
    }
}
