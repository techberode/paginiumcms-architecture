<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\SystemUpdate;

use PaginiumCMS\Core\SystemUpdate\Services\GitHubReleaseClient;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class GitHubReleaseClientTest extends TestCase
{
    public function testNormalizeCommitsExtractsSafeFieldsAndTruncatesMessage(): void
    {
        $client = new GitHubReleaseClient();
        $method = new ReflectionMethod(GitHubReleaseClient::class, 'normalizeCommits');

        $longMessage = str_repeat('x', 250);
        /** @var list<array<string, mixed>> $result */
        $result = $method->invoke($client, [
            'commits' => [
                [
                    'sha' => 'abcdef1234567890abcdef1234567890abcdef12',
                    'html_url' => 'https://github.com/org/repo/commit/abcdef1',
                    'commit' => [
                        'message' => $longMessage,
                        'author' => [
                            'name' => 'Alice',
                            'date' => '2026-07-29T10:00:00Z',
                        ],
                    ],
                ],
                [
                    'sha' => '1234567890abcdef1234567890abcdef12345678',
                    'commit' => [
                        'message' => "Line one\nLine two",
                        'author' => ['name' => 'Bob', 'date' => '2026-07-28T09:00:00Z'],
                    ],
                ],
            ],
        ]);

        $this->assertCount(2, $result);
        $this->assertSame('abcdef1', $result[0]['sha']);
        $this->assertSame('abcdef1234567890abcdef1234567890abcdef12', $result[0]['sha_full']);
        $this->assertSame('Alice', $result[0]['author']);
        $this->assertStringEndsWith('…', $result[0]['message']);
        $this->assertLessThanOrEqual(201, mb_strlen($result[0]['message']));
        $this->assertSame('Line one', $result[1]['message']);
    }

    public function testNormalizeCommitsReturnsEmptyForInvalidPayload(): void
    {
        $client = new GitHubReleaseClient();
        $method = new ReflectionMethod(GitHubReleaseClient::class, 'normalizeCommits');

        /** @var list<array<string, mixed>> $result */
        $result = $method->invoke($client, ['commits' => 'not-an-array']);

        $this->assertSame([], $result);
    }
}
