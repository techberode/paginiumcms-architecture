<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Health\Models;

use JsonSerializable;

class HealthStatus implements JsonSerializable
{
    public const STATUS_PASS = 'pass';
    public const STATUS_FAIL = 'fail';
    public const STATUS_WARN = 'warn';
    public const STATUS_SKIP = 'skip';

    private string $check;
    private string $status;
    private string $message;
    /** @var array<int|string, mixed> */
    private array $data = [];
    private float $duration;
    private string $timestamp;

    public function __construct(string $check, string $status, string $message = '')
    {
        $this->check = $check;
        $this->status = $status;
        $this->message = $message;
        $this->timestamp = date('Y-m-d H:i:s');
        $this->duration = 0;
    }

    public function getCheck(): string { return $this->check; }
    public function getStatus(): string { return $this->status; }
    public function isPass(): bool { return $this->status === self::STATUS_PASS; }
    public function isFail(): bool { return $this->status === self::STATUS_FAIL; }
    public function isWarn(): bool { return $this->status === self::STATUS_WARN; }
    public function isSkip(): bool { return $this->status === self::STATUS_SKIP; }
    public function getMessage(): string { return $this->message; }
    public function setMessage(string $message): self { $this->message = $message; return $this; }
    /**
     * @return array<int|string, mixed>
     */
    public function getData(): array { return $this->data; }
    /**
     * @param array<int|string, mixed> $data
     */
    public function setData(array $data): self { $this->data = $data; return $this; }
    public function getDuration(): float { return $this->duration; }
    public function setDuration(float $duration): self { $this->duration = $duration; return $this; }
    public function getTimestamp(): string { return $this->timestamp; }

    /**
     * @return array<int|string, mixed>
     */
    public function toArray(): array
    {
        return [
            'check' => $this->check,
            'status' => $this->status,
            'message' => $this->message,
            'data' => $this->data,
            'duration' => $this->duration,
            'timestamp' => $this->timestamp,
        ];
    }

    /**
     * @return array<int|string, mixed>
     */
    public function jsonSerialize(): array { return $this->toArray(); }
}
