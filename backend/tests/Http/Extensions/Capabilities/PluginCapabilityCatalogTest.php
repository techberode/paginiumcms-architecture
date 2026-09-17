<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Extensions\Capabilities;

use PaginiumCMS\Http\Extensions\Capabilities\PluginCapabilityCatalog;
use PHPUnit\Framework\TestCase;

final class PluginCapabilityCatalogTest extends TestCase
{
    public function testExactCatalogEntriesAreAllowed(): void
    {
        foreach (PluginCapabilityCatalog::all() as $capability) {
            $this->assertTrue(PluginCapabilityCatalog::isAllowed($capability));
        }
    }

    public function testOutboundHostCapabilityIsAllowed(): void
    {
        $this->assertTrue(PluginCapabilityCatalog::isAllowed('network:outbound:api.example.com'));
    }

    public function testUnknownAndMalformedCapabilitiesAreRejected(): void
    {
        $this->assertFalse(PluginCapabilityCatalog::isAllowed('shell:exec'));
        $this->assertFalse(PluginCapabilityCatalog::isAllowed('network:outbound:*'));
        $this->assertFalse(PluginCapabilityCatalog::isAllowed('network:outbound:https://evil.test'));
        $this->assertFalse(PluginCapabilityCatalog::isAllowed('network:outbound:api.example.com:443'));
        $this->assertFalse(PluginCapabilityCatalog::isAllowed('content:read:all'));
    }
}
