<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Git\Contracts;

/**
 * Outbound GitHub REST transport for the API publisher (It.70).
 */
interface GitHubApiTransport
{
    /**
     * @param array<string, mixed>|null $body
     *
     * @return array<string, mixed>
     */
    public function request(string $method, string $url, string $token, ?array $body = null): array;
}
