<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Feeds\Services;

use PaginiumCMS\Core\Feeds\Services\RobotsTxtGenerator;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class RobotsTxtGeneratorTest extends TestCase
{
    public function testRobotsIncludesSitemapWhenFeedsEnabled(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnMap([
            ['feeds', ['enabled' => true]],
            ['general', ['siteUrl' => 'https://example.com']],
        ]);

        $generator = new RobotsTxtGenerator($settings);
        $body = $generator->generate();

        $this->assertStringContainsString('User-agent: *', $body);
        $this->assertStringContainsString('Allow: /', $body);
        $this->assertStringContainsString('Sitemap: https://example.com/sitemap.xml', $body);
    }

    public function testRobotsOmitsSitemapWhenFeedsDisabled(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnMap([
            ['feeds', ['enabled' => false]],
            ['general', ['siteUrl' => 'https://example.com']],
        ]);

        $generator = new RobotsTxtGenerator($settings);
        $body = $generator->generate();

        $this->assertStringNotContainsString('Sitemap:', $body);
    }
}
