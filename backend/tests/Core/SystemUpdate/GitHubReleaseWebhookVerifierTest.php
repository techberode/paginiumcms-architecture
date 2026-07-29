<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\SystemUpdate;

use PaginiumCMS\Core\SystemUpdate\Services\GitHubReleaseWebhookVerifier;
use PHPUnit\Framework\TestCase;

final class GitHubReleaseWebhookVerifierTest extends TestCase
{
    public function testVerifyAcceptsValidSha256Signature(): void
    {
        $secret = 'super-secret';
        $body = '{"action":"published","release":{"tag_name":"v2.1.0-beta.18"}}';
        $signature = 'sha256=' . hash_hmac('sha256', $body, $secret);

        $verifier = new GitHubReleaseWebhookVerifier();

        $this->assertTrue($verifier->verify($body, $signature, $secret));
    }

    public function testVerifyRejectsInvalidSignature(): void
    {
        $verifier = new GitHubReleaseWebhookVerifier();

        $this->assertFalse($verifier->verify('{}', 'sha256=deadbeef', 'secret'));
        $this->assertFalse($verifier->verify('{}', '', 'secret'));
        $this->assertFalse($verifier->verify('{}', 'sha256=abc', ''));
    }
}
