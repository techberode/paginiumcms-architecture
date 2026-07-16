<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Analytics\Models;

use JsonSerializable;

class Visit implements JsonSerializable
{
    private string $id;
    private string $visitorId;
    private string $sessionId;
    private string $timestamp;
    private string $ip;
    private ?string $userAgent;
    private ?string $referer;
    private ?string $requestUri;
    private ?string $requestMethod;
    private int $responseCode;
    private int $duration;
    private ?string $userId;
    /** @var array<int|string, mixed> */
    private array $meta;

    public function __construct()
    {
        $this->id = uniqid('visit_', true);
        $this->visitorId = $this->generateVisitorId();
        $this->sessionId = session_id() ?: uniqid('sess_', true);
        $this->timestamp = date('Y-m-d H:i:s');
        $this->ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $this->userAgent = null;
        $this->referer = null;
        $this->requestUri = null;
        $this->requestMethod = null;
        $this->responseCode = 200;
        $this->duration = 0;
        $this->userId = null;
        $this->meta = [];
    }

    private function generateVisitorId(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        return md5($ip . $ua . $acceptLanguage);
    }

    public function getId(): string { return $this->id; }
    public function getVisitorId(): string { return $this->visitorId; }
    public function getSessionId(): string { return $this->sessionId; }
    public function getTimestamp(): string { return $this->timestamp; }
    public function setTimestamp(string $timestamp): self { $this->timestamp = $timestamp; return $this; }
    public function getIp(): string { return $this->ip; }
    public function setIp(string $ip): self { $this->ip = $ip; return $this; }
    public function getUserAgent(): ?string { return $this->userAgent; }
    public function setUserAgent(?string $ua): self { $this->userAgent = $ua; return $this; }
    public function getReferer(): ?string { return $this->referer; }
    public function setReferer(?string $referer): self { $this->referer = $referer; return $this; }
    public function getRequestUri(): ?string { return $this->requestUri; }
    public function setRequestUri(?string $uri): self { $this->requestUri = $uri; return $this; }
    public function getRequestMethod(): ?string { return $this->requestMethod; }
    public function setRequestMethod(?string $method): self { $this->requestMethod = $method; return $this; }
    public function getResponseCode(): int { return $this->responseCode; }
    public function setResponseCode(int $code): self { $this->responseCode = $code; return $this; }
    public function getDuration(): int { return $this->duration; }
    public function setDuration(int $duration): self { $this->duration = $duration; return $this; }
    public function getUserId(): ?string { return $this->userId; }
    public function setUserId(?string $userId): self { $this->userId = $userId; return $this; }
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
            'sessionId' => $this->sessionId,
            'timestamp' => $this->timestamp,
            'ip' => $this->ip,
            'userAgent' => $this->userAgent,
            'referer' => $this->referer,
            'requestUri' => $this->requestUri,
            'requestMethod' => $this->requestMethod,
            'responseCode' => $this->responseCode,
            'duration' => $this->duration,
            'userId' => $this->userId,
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
