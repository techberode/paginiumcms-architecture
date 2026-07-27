<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Analytics\Services;

use PaginiumCMS\Core\Analytics\Services\GeoIPService;
use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;
use PHPUnit\Framework\TestCase;

final class GeoIPServiceTest extends TestCase
{
    public function testUsesHttpsApiEndpoint(): void
    {
        $service = new GeoIPService(new OutboundUrlGuard(false, false));
        $reflection = new \ReflectionClass($service);
        $prop = $reflection->getProperty('apiUrl');

        $this->assertSame('https://ip-api.com/json/', $prop->getValue($service));
    }

    public function testLocalIpDoesNotCallRemoteApi(): void
    {
        $service = new GeoIPService(new OutboundUrlGuard(false, false));

        $location = $service->getLocation('192.168.1.10');
        $this->assertNotNull($location);
        $this->assertSame('Localhost', $location->getCountry());
    }
}
