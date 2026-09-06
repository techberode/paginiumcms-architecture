<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Security\Firewall;

use PaginiumCMS\Core\Security\Firewall\FirewallBanStore;
use PaginiumCMS\Core\Security\Firewall\FirewallIncidentLogger;
use PaginiumCMS\Core\Security\Firewall\FirewallScanner;
use PaginiumCMS\Core\Security\Firewall\FirewallScenarioRegistry;
use PaginiumCMS\Core\Security\Firewall\FirewallService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PHPUnit\Framework\TestCase;

final class FirewallServiceBotBlockingTest extends TestCase
{
    public function testBlocksEmptyUserAgentWhenEnabled(): void
    {
        $service = $this->createService([
            'enabled' => true,
            'blockEmptyUserAgent' => true,
            'blockScraperTools' => false,
        ]);

        $match = $service->inspectRequest('203.0.113.10', '/blog', '', '');

        $this->assertNotNull($match);
        $this->assertSame('bad_bot_ua', $match['id'] ?? null);
    }

    public function testAllowsEmptyUserAgentWhenDisabled(): void
    {
        $service = $this->createService([
            'enabled' => true,
            'blockEmptyUserAgent' => false,
            'blockScraperTools' => false,
        ]);

        $match = $service->inspectRequest('203.0.113.10', '/blog', '', '');

        $this->assertNull($match);
    }

    public function testBlocksScraperToolsWhenEnabled(): void
    {
        $service = $this->createService([
            'enabled' => true,
            'blockEmptyUserAgent' => false,
            'blockScraperTools' => true,
        ]);

        $match = $service->inspectRequest('203.0.113.10', '/blog', '', 'curl/8.4.0');

        $this->assertNotNull($match);
        $this->assertSame('scraper_tool_bot', $match['id'] ?? null);
    }

    public function testNeverBlocksGooglebotEvenWithScraperSetting(): void
    {
        $service = $this->createService([
            'enabled' => true,
            'blockEmptyUserAgent' => false,
            'blockScraperTools' => true,
        ]);

        $match = $service->inspectRequest(
            '203.0.113.10',
            '/blog',
            '',
            'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'
        );

        $this->assertNull($match);
    }

    /**
     * @param array<string, mixed> $firewallSettings
     */
    private function createService(array $firewallSettings): FirewallService
    {
        $basePath = sys_get_temp_dir() . '/paginium-firewall-bot-' . uniqid('', true);
        mkdir($basePath . '/data/security/firewall', 0777, true);

        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('getBasePath')->willReturn($basePath);

        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnCallback(static function (string $group) use ($firewallSettings): array {
            if ($group === 'firewall') {
                return $firewallSettings;
            }

            return [];
        });

        $banStore = new FirewallBanStore($reader, $settings);
        $incidentLogger = new FirewallIncidentLogger($reader, $settings);

        return new FirewallService(
            $settings,
            new FirewallScanner(new FirewallScenarioRegistry()),
            $banStore,
            $incidentLogger
        );
    }
}
