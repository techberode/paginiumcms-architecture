<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Analytics\Services;

use PaginiumCMS\Core\Analytics\Models\GeoLocation;

/**
 * Služba pre geolokáciu IP adries.
 * Používa free API alebo lokálnu databázu.
 */
class GeoIPService
{
    private string $apiUrl = 'http://ip-api.com/json/';
    private array $cache = [];

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
            $response = @file_get_contents($this->apiUrl . $ip);
            if ($response === false) {
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

    /**
     * Získa geolokačné údaje pre aktuálnu IP.
     */
    public function getCurrentLocation(): ?GeoLocation
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        return $this->getLocation($ip);
    }
}
