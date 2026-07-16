<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Health\Models;

use JsonSerializable;

class HealthReport implements JsonSerializable
{
    private string $id;
    private string $timestamp;
    private string $status;
    /** @var array<int|string, mixed> */
    private array $checks = [];

    public function __construct()
    {
        $this->id = uniqid('health_', true);
        $this->timestamp = date('Y-m-d H:i:s');
        $this->status = HealthStatus::STATUS_PASS;
    }

    public function addCheck(HealthStatus $check): self
    {
        $this->checks[] = $check;

        if ($check->isFail()) {
            $this->status = HealthStatus::STATUS_FAIL;
        } elseif ($check->isWarn() && $this->status !== HealthStatus::STATUS_FAIL) {
            $this->status = HealthStatus::STATUS_WARN;
        }

        return $this;
    }

    /**
     * @param array<int|string, mixed> $checks
     */
    public function addChecks(array $checks): self
    {
        foreach ($checks as $check) {
            if ($check instanceof HealthStatus) {
                $this->addCheck($check);
            }
        }
        return $this;
    }

    public function getId(): string { return $this->id; }
    public function getTimestamp(): string { return $this->timestamp; }
    public function getStatus(): string { return $this->status; }
    public function isPass(): bool { return $this->status === HealthStatus::STATUS_PASS; }
    public function isFail(): bool { return $this->status === HealthStatus::STATUS_FAIL; }
    public function isWarn(): bool { return $this->status === HealthStatus::STATUS_WARN; }
    /**
     * @return array<int|string, mixed>
     */
    public function getChecks(): array { return $this->checks; }

    /**
     * @return array<int|string, mixed>
     */
    public function getSummary(): array
    {
        $summary = [
            'total' => 0,
            'pass' => 0,
            'fail' => 0,
            'warn' => 0,
            'skip' => 0,
        ];

        foreach ($this->checks as $check) {
            $summary['total']++;
            $status = $check->getStatus();
            if (isset($summary[$status])) {
                $summary[$status]++;
            }
        }

        return $summary;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'timestamp' => $this->timestamp,
            'status' => $this->status,
            'summary' => $this->getSummary(),
            'checks' => array_map(fn($c) => $c->toArray(), $this->checks),
        ];
    }

    /**
     * @return array<int|string, mixed>
     */
    public function jsonSerialize(): array { return $this->toArray(); }
}
