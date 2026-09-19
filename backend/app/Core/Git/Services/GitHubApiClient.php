<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Git\Services;

use PaginiumCMS\Core\Git\Contracts\GitHubApiTransport;
use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * curl GitHub REST client with SSRF guard (It.70 github_api publisher).
 */
final class GitHubApiClient implements GitHubApiTransport
{
    public function request(string $method, string $url, string $token, ?array $body = null): array
    {
        OutboundUrlGuard::fromEnv()->assertAllowed($url);

        $handle = curl_init($url);
        if ($handle === false) {
            throw new RuntimeException('GitHub API request init failed');
        }

        $verb = strtoupper($method);
        if (!in_array($verb, ['GET', 'POST', 'PATCH', 'PUT', 'DELETE'], true)) {
            throw new RuntimeException('Unsupported GitHub API method');
        }

        $headers = [
            'Accept: application/vnd.github+json',
            'User-Agent: PaginiumCMS-GitPublish',
            'X-GitHub-Api-Version: 2022-11-28',
            'Authorization: Bearer ' . $token,
        ];

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $verb,
        ];

        if ($body !== null) {
            $encoded = JsonHelper::encode($body);
            $options[CURLOPT_POSTFIELDS] = $encoded;
            $headers[] = 'Content-Type: application/json';
            $options[CURLOPT_HTTPHEADER] = $headers;
        }

        curl_setopt_array($handle, $options);

        $raw = curl_exec($handle);
        $http = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if (!is_string($raw)) {
            throw new RuntimeException('GitHub API request failed: ' . ($error !== '' ? $error : 'empty response'));
        }

        if ($http >= 400) {
            throw new RuntimeException('GitHub API HTTP ' . $http);
        }

        if ($raw === '') {
            return [];
        }

        return $this->stringKeyed(JsonHelper::decode($raw));
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
