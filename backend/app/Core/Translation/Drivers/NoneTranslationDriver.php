<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Translation\Drivers;

use PaginiumCMS\Core\Translation\Contracts\TranslationProviderInterface;
use PaginiumCMS\Core\Translation\Exception\TranslationException;

/**
 * Disabled / manual-only provider (It.76). Never opens a socket.
 */
final class NoneTranslationDriver implements TranslationProviderInterface
{
    public function id(): string
    {
        return 'none';
    }

    public function health(): array
    {
        return ['ok' => false, 'error' => 'Translation provider is none'];
    }

    public function translate(string $text, string $sourceLocale, string $targetLocale): array
    {
        throw new TranslationException('Translation is disabled', 503, 'DISABLED');
    }
}
