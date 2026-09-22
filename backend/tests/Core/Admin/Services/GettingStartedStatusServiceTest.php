<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Admin\Services;

use PaginiumCMS\Core\Admin\Services\GettingStartedStatusService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GettingStartedStatusServiceTest extends TestCase
{
    public function testDefaultSiteNameIsNotConfigured(): void
    {
        $repo = $this->createMock(SettingsRepositoryInterface::class);
        $repo->method('group')->willReturnMap([
            ['general', ['siteName' => 'PaginiumCMS', 'siteDescription' => '']],
            ['branding', ['logoUrl' => '', 'faviconUrl' => '']],
            ['smtp', ['host' => '', 'fromEmail' => '']],
        ]);

        $service = new GettingStartedStatusService($repo);
        $probes = $service->probes();

        $this->assertFalse($probes['siteName']);
        $this->assertFalse($probes['mail']);
    }

    public function testCustomSiteNameIsConfigured(): void
    {
        $repo = $this->createMock(SettingsRepositoryInterface::class);
        $repo->method('group')->willReturnMap([
            ['general', ['siteName' => 'Môj web', 'siteDescription' => '']],
            ['branding', ['logoUrl' => '', 'faviconUrl' => '']],
            ['smtp', ['host' => 'smtp.example.com', 'fromEmail' => 'noreply@example.com']],
        ]);

        $service = new GettingStartedStatusService($repo);
        $probes = $service->probes();

        $this->assertTrue($probes['siteName']);
        $this->assertTrue($probes['mail']);
    }

    public function testSiteDescriptionCanSatisfySiteNameProbe(): void
    {
        $repo = $this->createMock(SettingsRepositoryInterface::class);
        $repo->method('group')->willReturnMap([
            ['general', ['siteName' => 'PaginiumCMS', 'siteDescription' => 'Popis môjho webu']],
            ['branding', ['logoUrl' => '', 'faviconUrl' => '']],
            ['smtp', ['host' => '', 'fromEmail' => '']],
        ]);

        $service = new GettingStartedStatusService($repo);
        $probes = $service->probes();

        $this->assertTrue($probes['siteName']);
    }
}
