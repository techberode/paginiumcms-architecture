<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Analytics\Models;

use JsonSerializable;

class GeoLocation implements JsonSerializable
{
    private string $ip;
    private ?string $country = null;
    private ?string $countryCode = null;
    private ?string $city = null;
    private ?string $region = null;
    private ?string $regionCode = null;
    private ?float $latitude = null;
    private ?float $longitude = null;
    private ?string $timezone = null;
    private ?string $isp = null;
    private ?string $organization = null;
    private ?string $as = null;

    public function __construct(string $ip)
    {
        $this->ip = $ip;
    }

    public function getIp(): string { return $this->ip; }
    public function getCountry(): ?string { return $this->country; }
    public function setCountry(?string $country): self { $this->country = $country; return $this; }
    public function getCountryCode(): ?string { return $this->countryCode; }
    public function setCountryCode(?string $code): self { $this->countryCode = $code; return $this; }
    public function getCity(): ?string { return $this->city; }
    public function setCity(?string $city): self { $this->city = $city; return $this; }
    public function getRegion(): ?string { return $this->region; }
    public function setRegion(?string $region): self { $this->region = $region; return $this; }
    public function getRegionCode(): ?string { return $this->regionCode; }
    public function setRegionCode(?string $code): self { $this->regionCode = $code; return $this; }
    public function getLatitude(): ?float { return $this->latitude; }
    public function setLatitude(?float $lat): self { $this->latitude = $lat; return $this; }
    public function getLongitude(): ?float { return $this->longitude; }
    public function setLongitude(?float $lng): self { $this->longitude = $lng; return $this; }
    public function getTimezone(): ?string { return $this->timezone; }
    public function setTimezone(?string $tz): self { $this->timezone = $tz; return $this; }
    public function getIsp(): ?string { return $this->isp; }
    public function setIsp(?string $isp): self { $this->isp = $isp; return $this; }
    public function getOrganization(): ?string { return $this->organization; }
    public function setOrganization(?string $org): self { $this->organization = $org; return $this; }
    public function getAs(): ?string { return $this->as; }
    public function setAs(?string $as): self { $this->as = $as; return $this; }

    /**
     * @return array<int|string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'ip' => $this->ip,
            'country' => $this->country,
            'countryCode' => $this->countryCode,
            'city' => $this->city,
            'region' => $this->region,
            'regionCode' => $this->regionCode,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'timezone' => $this->timezone,
            'isp' => $this->isp,
            'organization' => $this->organization,
            'as' => $this->as,
        ];
    }
}
