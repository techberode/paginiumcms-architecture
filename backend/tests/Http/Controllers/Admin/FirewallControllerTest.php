<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Tests\Http\TestCase;

final class FirewallControllerTest extends TestCase
{
    public function testStatsRequiresAdmin(): void
    {
        $request = $this->createJsonRequest('GET', '/api/admin/firewall/stats');
        $response = $this->handleRequest($request);
        $this->assertContains($response->getStatusCode(), [401, 403]);
    }

    public function testAdminCanManageBansAndWhitelist(): void
    {
        $login = $this->loginAsAdminUser();
        $this->assertEquals(200, $login['response']->getStatusCode());

        $ip = '203.0.113.77';

        $banRequest = $this->createJsonRequest('POST', '/api/admin/firewall/bans', [
            'ip' => $ip,
            'permanent' => false,
            'reason' => 'manual-test',
        ]);
        $banResponse = $this->handleRequest($banRequest);
        $banData = $this->getJsonResponse($banResponse);
        $this->assertEquals(201, $banResponse->getStatusCode());
        $this->assertTrue($banData['success']);

        $listRequest = $this->createJsonRequest('GET', '/api/admin/firewall/bans');
        $listResponse = $this->handleRequest($listRequest);
        $listData = $this->getJsonResponse($listResponse);
        $this->assertTrue($listData['success']);

        $found = false;
        foreach ($listData['data'] as $entry) {
            if (($entry['ip'] ?? '') === $ip) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);

        $unbanRequest = $this->createJsonRequest('DELETE', '/api/admin/firewall/bans/' . urlencode($ip));
        $unbanResponse = $this->handleRequest($unbanRequest);
        $this->assertEquals(200, $unbanResponse->getStatusCode());

        $whitelistRequest = $this->createJsonRequest('POST', '/api/admin/firewall/whitelist', ['ip' => $ip]);
        $whitelistResponse = $this->handleRequest($whitelistRequest);
        $this->assertEquals(200, $whitelistResponse->getStatusCode());

        $statsRequest = $this->createJsonRequest('GET', '/api/admin/firewall/stats');
        $statsResponse = $this->handleRequest($statsRequest);
        $statsData = $this->getJsonResponse($statsResponse);
        $this->assertEquals(200, $statsResponse->getStatusCode());
        $this->assertTrue($statsData['success']);
        $this->assertArrayHasKey('active_jails', $statsData['data']);
    }
}
