<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Settings\Services;

use InvalidArgumentException;
use PaginiumCMS\Core\Settings\Services\SocialLinksNormalizer;
use PHPUnit\Framework\TestCase;

final class SocialLinksNormalizerTest extends TestCase
{
    public function testNormalizesGithubLink(): void
    {
        $json = json_encode([
            ['platform' => 'github', 'url' => 'https://github.com/example/repo', 'label' => 'Repo'],
        ], JSON_THROW_ON_ERROR);

        $links = SocialLinksNormalizer::normalizeJson($json);

        $this->assertCount(1, $links);
        $this->assertSame('github', $links[0]['platform']);
        $this->assertSame('https://github.com/example/repo', $links[0]['url']);
        $this->assertTrue($links[0]['enabled']);
    }

    public function testRejectsInvalidPlatform(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SocialLinksNormalizer::normalizeJson(json_encode([
            ['platform' => 'tiktok', 'url' => 'https://example.com'],
        ], JSON_THROW_ON_ERROR));
    }

    public function testPublicLinksSkipsDisabled(): void
    {
        $json = SocialLinksNormalizer::encode([
            [
                'id' => 'a',
                'platform' => 'github',
                'url' => 'https://github.com/a',
                'label' => 'A',
                'enabled' => false,
            ],
            [
                'id' => 'b',
                'platform' => 'gitlab',
                'url' => 'https://gitlab.com/b',
                'label' => 'B',
                'enabled' => true,
            ],
        ]);

        $public = SocialLinksNormalizer::publicLinks($json, true);

        $this->assertCount(1, $public);
        $this->assertSame('gitlab', $public[0]['platform']);
    }
}
