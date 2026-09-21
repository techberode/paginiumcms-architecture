<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Playground;

use PaginiumCMS\Modules\Playground\PlaygroundGitArchiveLocator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PlaygroundGitArchiveLocatorTest extends TestCase
{
    public function testMapsGithubRepoToZipball(): void
    {
        $url = PlaygroundGitArchiveLocator::zipUrl('https://github.com/acme/design-system.git', 'v1.2.0');
        $this->assertSame(
            'https://api.github.com/repos/acme/design-system/zipball/v1.2.0',
            $url
        );
    }

    public function testMapsGitlabProject(): void
    {
        $url = PlaygroundGitArchiveLocator::zipUrl('https://gitlab.com/acme/ui-kit', 'main');
        $this->assertSame(
            'https://gitlab.com/api/v4/projects/acme%2Fui-kit/repository/archive.zip?sha=main',
            $url
        );
    }

    public function testKeepsDirectZipUrl(): void
    {
        $url = PlaygroundGitArchiveLocator::zipUrl('https://example.com/packs/widgets.zip', 'ignored');
        $this->assertSame('https://example.com/packs/widgets.zip', $url);
    }

    public function testRejectsDotDotRef(): void
    {
        $this->expectException(RuntimeException::class);
        PlaygroundGitArchiveLocator::zipUrl('https://github.com/acme/design-system', '../main');
    }

    public function testRejectsHttpUrl(): void
    {
        $this->expectException(RuntimeException::class);
        PlaygroundGitArchiveLocator::zipUrl('http://github.com/acme/design-system', 'main');
    }

    public function testRejectsEmbeddedCredentials(): void
    {
        $this->expectException(RuntimeException::class);
        PlaygroundGitArchiveLocator::zipUrl('https://token@github.com/acme/design-system', 'main');
    }

    public function testRejectsUntrustedGitlabSubdomain(): void
    {
        $this->expectException(RuntimeException::class);
        PlaygroundGitArchiveLocator::zipUrl('https://pages.gitlab.com/acme/design-system', 'main');
    }
}
