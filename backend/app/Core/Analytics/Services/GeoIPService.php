<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Analytics\Services;

use PaginiumCMS\Core\Analytics\Models\GeoLocation;
use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;

/**
 * Geolocation lookup for analytics (audit A6-GEOIP: HTTPS + OutboundUrlGuard).
 */
class GeoIPService
{
    private string $apiUrl = 'https://ip-api.com/json/';

    /** @var array<int|string, mixed> */
    private array $cache = [];

    public function __construct(
        private ?OutboundUrlGuard $outboundGuard = null
    ) {
    }

    /**
     * Získa geolokačné údaje pre IP adresu.
     */
    public function getLocation(string $ip): ?GeoLocation
    {
        // Kontrola cache
        if (isset($this->cache[$ip])) {
            return $this->cache[$ip];
        }

        // Lokálna IP
        if ($ip === '127.0.0.1' || $ip === '::1' || strpos($ip, '192.168.') === 0) {
            $location = new GeoLocation($ip);
            $location->setCountry('Localhost');
            $location->setCity('Local');
            $this->cache[$ip] = $location;
            return $location;
        }

        try {
            $response = $this->fetchRemoteJson($this->apiUrl . $ip);
            if ($response === null) {
                return null;
            }

            $data = json_decode($response, true);
            if (!$data || ($data['status'] ?? '') !== 'success') {
                return null;
            }

            $location = new GeoLocation($ip);
            $location->setCountry($data['country'] ?? null);
            $location->setCountryCode($data['countryCode'] ?? null);
            $location->setCity($data['city'] ?? null);
            $location->setRegion($data['regionName'] ?? null);
            $location->setRegionCode($data['region'] ?? null);
            $location->setLatitude($data['lat'] ?? null);
            $location->setLongitude($data['lon'] ?? null);
            $location->setTimezone($data['timezone'] ?? null);
            $location->setIsp($data['isp'] ?? null);
            $location->setOrganization($data['org'] ?? null);
            $location->setAs($data['as'] ?? null);

            $this->cache[$ip] = $location;
            return $location;
        } catch (\Exception) {
            return null;
        }
    }

    private function fetchRemoteJson(string $url): ?string
    {
        $guard = $this->outboundGuard ?? OutboundUrlGuard::fromEnv();
        if (!$guard->isAllowed($url)) {
            return null;
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 2,
                'ignore_errors' => true,
            ],
        ]);

        $previousHandler = set_error_handler(static function (): bool {
            return true;
        });

        try {
            $response = file_get_contents($url, false, $context);
        } finally {
            restore_error_handler();
        }

        if ($response === false) {
            return null;
        }

        return $response;
    }

    /**
     * Získa geolokačné údaje pre aktuálnu IP.
     */
    public function getCurrentLocation(): ?GeoLocation
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        return $this->getLocation($ip);
    }
}
