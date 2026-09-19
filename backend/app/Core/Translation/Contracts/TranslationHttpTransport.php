<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Translation\Contracts;

/**
 * Outbound HTTP for translation providers (It.76).
 */
interface TranslationHttpTransport
{
    /**
     * @param array<string, mixed>|null $body
     * @param array<string, string> $headers
     *
     * @return array<string, mixed>
     */
    public function request(string $method, string $url, ?array $body, int $timeoutSeconds, array $headers = []): array;
}
