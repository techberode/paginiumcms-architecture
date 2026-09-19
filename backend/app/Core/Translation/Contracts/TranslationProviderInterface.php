<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Translation\Contracts;

/**
 * Assisted-translation driver (It.76). Cloud drivers in It.77 implement the same contract.
 */
interface TranslationProviderInterface
{
    public function id(): string;

    /**
     * @return array{ok: bool, error?: string}
     */
    public function health(): array;

    /**
     * @return array{text: string, characters: int}
     */
    public function translate(string $text, string $sourceLocale, string $targetLocale): array;
}
