<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\SystemUpdate\Services;

/**
 * GitHub release webhook HMAC verification (It.63 v3).
 */
final class GitHubReleaseWebhookVerifier
{
    public function verify(string $rawBody, string $signatureHeader, string $secret): bool
    {
        $secret = trim($secret);
        if ($secret === '' || $signatureHeader === '') {
            return false;
        }

        if (!str_starts_with($signatureHeader, 'sha256=')) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $signatureHeader);
    }
}
