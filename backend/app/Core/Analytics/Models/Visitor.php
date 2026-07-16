<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Analytics\Models;

use JsonSerializable;

class Visitor implements JsonSerializable
{
    private string $id;
    private string $visitorId;
    private string $firstVisit;
    private string $lastVisit;
    private int $visitCount;
    private string $ip;
    private ?string $country;
    private ?string $city;
    private ?string $region;
    private ?string $latitude;
    private ?string $longitude;
    private ?string $device;
    private ?string $os;
    private ?string $browser;
    private ?string $deviceType;
    /** @var array<int|string, mixed> */
    private array $meta = [];

    public function __construct(string $visitorId)
    {
        $this->id = uniqid('visitor_', true);
        $this->visitorId = $visitorId;
        $this->firstVisit = date('Y-m-d H:i:s');
        $this->lastVisit = date('Y-m-d H:i:s');
        $this->visitCount = 1;
        $this->ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    public function getId(): string { return $this->id; }
    public function getVisitorId(): string { return $this->visitorId; }
    public function getFirstVisit(): string { return $this->firstVisit; }
    public function getLastVisit(): string { return $this->lastVisit; }
    public function setLastVisit(string $time): self { $this->lastVisit = $time; return $this; }
    public function getVisitCount(): int { return $this->visitCount; }
    public function incrementVisitCount(): self { $this->visitCount++; return $this; }
    public function getIp(): string { return $this->ip; }
    public function setIp(string $ip): self { $this->ip = $ip; return $this; }
    public function getCountry(): ?string { return $this->country; }
    public function setCountry(?string $country): self { $this->country = $country; return $this; }
    public function getCity(): ?string { return $this->city; }
    public function setCity(?string $city): self { $this->city = $city; return $this; }
    public function getRegion(): ?string { return $this->region; }
    public function setRegion(?string $region): self { $this->region = $region; return $this; }
    public function getLatitude(): ?string { return $this->latitude; }
    public function setLatitude(?string $lat): self { $this->latitude = $lat; return $this; }
    public function getLongitude(): ?string { return $this->longitude; }
    public function setLongitude(?string $lng): self { $this->longitude = $lng; return $this; }
    public function getDevice(): ?string { return $this->device; }
    public function setDevice(?string $device): self { $this->device = $device; return $this; }
    public function getOs(): ?string { return $this->os; }
    public function setOs(?string $os): self { $this->os = $os; return $this; }
    public function getBrowser(): ?string { return $this->browser; }
    public function setBrowser(?string $browser): self { $this->browser = $browser; return $this; }
    public function getDeviceType(): ?string { return $this->deviceType; }
    public function setDeviceType(?string $type): self { $this->deviceType = $type; return $this; }
    /**
     * @return array<int|string, mixed>
     */
    public function getMeta(): array { return $this->meta; }
    /**
     * @param array<int|string, mixed> $meta
     */
    public function setMeta(array $meta): self { $this->meta = $meta; return $this; }

    /**
     * @return array<int|string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'visitorId' => $this->visitorId,
            'firstVisit' => $this->firstVisit,
            'lastVisit' => $this->lastVisit,
            'visitCount' => $this->visitCount,
            'ip' => $this->ip,
            'country' => $this->country,
            'city' => $this->city,
            'region' => $this->region,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'device' => $this->device,
            'os' => $this->os,
            'browser' => $this->browser,
            'deviceType' => $this->deviceType,
            'meta' => $this->meta,
        ];
    }

    /**
     * @return array<int|string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
