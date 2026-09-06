<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Analytics\Services;

use PaginiumCMS\Core\Analytics\Services\GeoIPService;
use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;
use PHPUnit\Framework\TestCase;

final class GeoIPServiceTest extends TestCase
{
    public function testUsesHttpsPrimaryProvider(): void
    {
        $service = new GeoIPService(new OutboundUrlGuard(false, false));
        $reflection = new \ReflectionClass($service);
        $prop = $reflection->getProperty('primaryApiUrl');

        $this->assertSame('https://ipapi.co/', $prop->getValue($service));
    }

    public function testLocalIpDoesNotCallRemoteApi(): void
    {
        $service = new GeoIPService(new OutboundUrlGuard(false, false));

        $location = $service->getLocation('192.168.1.10');
        $this->assertNotNull($location);
        $this->assertSame('Localhost', $location->getCountry());
    }

    public function testMapsIpApiCoPayload(): void
    {
        $location = GeoIPService::locationFromIpApiCo('8.8.8.8', [
            'ip' => '8.8.8.8',
            'country' => 'US',
            'country_name' => 'United States',
            'city' => 'Mountain View',
            'region' => 'California',
            'region_code' => 'CA',
            'latitude' => 37.4056,
            'longitude' => -122.0775,
            'timezone' => 'America/Los_Angeles',
            'org' => 'Google LLC',
            'asn' => 'AS15169',
        ]);

        $this->assertNotNull($location);
        $this->assertSame('United States', $location->getCountry());
        $this->assertSame('US', $location->getCountryCode());
        $this->assertSame('Mountain View', $location->getCity());
    }

    public function testIpApiCoErrorPayloadReturnsNull(): void
    {
        $location = GeoIPService::locationFromIpApiCo('8.8.8.8', [
            'error' => true,
            'reason' => 'RateLimited',
        ]);

        $this->assertNull($location);
    }

    public function testMapsIpApiComPayload(): void
    {
        $location = GeoIPService::locationFromIpApiCom('8.8.8.8', [
            'status' => 'success',
            'country' => 'United States',
            'countryCode' => 'US',
            'city' => 'Ashburn',
            'regionName' => 'Virginia',
            'region' => 'VA',
            'lat' => 39.03,
            'lon' => -77.5,
            'timezone' => 'America/New_York',
            'isp' => 'Google LLC',
            'org' => 'Google Public DNS',
            'as' => 'AS15169 Google LLC',
        ]);

        $this->assertNotNull($location);
        $this->assertSame('United States', $location->getCountry());
        $this->assertSame('US', $location->getCountryCode());
    }
}
