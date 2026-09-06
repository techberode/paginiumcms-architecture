<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Analytics\Services;

use PaginiumCMS\Core\Analytics\Models\GeoLocation;
use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;

/**
 * Geolocation lookup for analytics (HTTPS providers + OutboundUrlGuard).
 *
 * Primary: ipapi.co (HTTPS, free tier). Fallback: ip-api.com over HTTP in dev/test only
 * (free ip-api.com no longer serves HTTPS without a paid key).
 */
class GeoIPService
{
    private string $primaryApiUrl = 'https://ipapi.co/';

    private string $fallbackApiUrl = 'http://ip-api.com/json/';

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
        if (isset($this->cache[$ip])) {
            return $this->cache[$ip];
        }

        if ($this->isPrivateOrLocalIp($ip)) {
            $location = new GeoLocation($ip);
            $location->setCountry('Localhost');
            $location->setCity('Local');
            $this->cache[$ip] = $location;

            return $location;
        }

        $location = $this->lookupIpApiCo($ip);
        if ($location === null) {
            $location = $this->lookupIpApiCom($ip);
        }

        if ($location !== null) {
            $this->cache[$ip] = $location;
        }

        return $location;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function locationFromIpApiCo(string $ip, array $data): ?GeoLocation
    {
        if (($data['error'] ?? false) === true) {
            return null;
        }

        $countryCode = isset($data['country']) && is_string($data['country']) && strlen($data['country']) === 2
            ? strtoupper($data['country'])
            : null;
        $countryName = isset($data['country_name']) && is_string($data['country_name'])
            ? $data['country_name']
            : null;

        if ($countryCode === null && $countryName === null) {
            return null;
        }

        $location = new GeoLocation($ip);
        $location->setCountry($countryName ?? $countryCode);
        $location->setCountryCode($countryCode);
        $location->setCity(isset($data['city']) && is_string($data['city']) ? $data['city'] : null);
        $location->setRegion(isset($data['region']) && is_string($data['region']) ? $data['region'] : null);
        $location->setRegionCode(
            isset($data['region_code']) && is_string($data['region_code']) ? $data['region_code'] : null
        );
        $location->setLatitude(isset($data['latitude']) && is_numeric($data['latitude']) ? (float) $data['latitude'] : null);
        $location->setLongitude(isset($data['longitude']) && is_numeric($data['longitude']) ? (float) $data['longitude'] : null);
        $location->setTimezone(isset($data['timezone']) && is_string($data['timezone']) ? $data['timezone'] : null);
        $location->setIsp(isset($data['org']) && is_string($data['org']) ? $data['org'] : null);
        $location->setOrganization(isset($data['org']) && is_string($data['org']) ? $data['org'] : null);
        $location->setAs(isset($data['asn']) && is_string($data['asn']) ? $data['asn'] : null);

        return $location;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function locationFromIpApiCom(string $ip, array $data): ?GeoLocation
    {
        if (($data['status'] ?? '') !== 'success') {
            return null;
        }

        $location = new GeoLocation($ip);
        $location->setCountry(isset($data['country']) && is_string($data['country']) ? $data['country'] : null);
        $location->setCountryCode(
            isset($data['countryCode']) && is_string($data['countryCode']) ? strtoupper($data['countryCode']) : null
        );
        $location->setCity(isset($data['city']) && is_string($data['city']) ? $data['city'] : null);
        $location->setRegion(isset($data['regionName']) && is_string($data['regionName']) ? $data['regionName'] : null);
        $location->setRegionCode(isset($data['region']) && is_string($data['region']) ? $data['region'] : null);
        $location->setLatitude(isset($data['lat']) && is_numeric($data['lat']) ? (float) $data['lat'] : null);
        $location->setLongitude(isset($data['lon']) && is_numeric($data['lon']) ? (float) $data['lon'] : null);
        $location->setTimezone(isset($data['timezone']) && is_string($data['timezone']) ? $data['timezone'] : null);
        $location->setIsp(isset($data['isp']) && is_string($data['isp']) ? $data['isp'] : null);
        $location->setOrganization(isset($data['org']) && is_string($data['org']) ? $data['org'] : null);
        $location->setAs(isset($data['as']) && is_string($data['as']) ? $data['as'] : null);

        return $location;
    }

    private function lookupIpApiCo(string $ip): ?GeoLocation
    {
        try {
            $response = $this->fetchRemoteJson($this->primaryApiUrl . rawurlencode($ip) . '/json/');
            if ($response === null) {
                return null;
            }

            /** @var array<string, mixed>|null $data */
            $data = json_decode($response, true);

            return is_array($data) ? self::locationFromIpApiCo($ip, $data) : null;
        } catch (\Exception) {
            return null;
        }
    }

    private function lookupIpApiCom(string $ip): ?GeoLocation
    {
        $url = $this->fallbackApiUrl . rawurlencode($ip);
        $guard = $this->outboundGuard ?? OutboundUrlGuard::fromEnv();
        if (!$guard->isAllowed($url)) {
            return null;
        }

        try {
            $response = $this->fetchRemoteJson($url);
            if ($response === null) {
                return null;
            }

            /** @var array<string, mixed>|null $data */
            $data = json_decode($response, true);

            return is_array($data) ? self::locationFromIpApiCom($ip, $data) : null;
        } catch (\Exception) {
            return null;
        }
    }

    private function isPrivateOrLocalIp(string $ip): bool
    {
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return true;
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }

        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
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
                'header' => "Accept: application/json\r\nUser-Agent: PaginiumCMS-Analytics/1.0\r\n",
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
