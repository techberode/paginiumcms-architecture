<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Performance;

/**
 * Request-local counters and timings for Performance Guard (Iteration 71, 85).
 */
final class PerformanceContext
{
    private bool $active = false;
    private int $storageReads = 0;
    private int $storageWrites = 0;
    private int $storageReadNs = 0;
    private int $storageWriteNs = 0;
    private int $sessionLockNs = 0;
    private int $sessionHeldNs = 0;
    private ?int $sessionActiveSinceNs = null;

    public function reset(): void
    {
        $this->active = true;
        $this->storageReads = 0;
        $this->storageWrites = 0;
        $this->storageReadNs = 0;
        $this->storageWriteNs = 0;
        $this->sessionLockNs = 0;
        $this->sessionHeldNs = 0;
        $this->sessionActiveSinceNs = null;
    }

    public function deactivate(): void
    {
        $this->active = false;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function recordStorageReadDuration(int $nanoseconds): void
    {
        if ($nanoseconds > 0) {
            $this->storageReadNs += $nanoseconds;
        }

        if ($this->active) {
            ++$this->storageReads;
        }
    }

    public function recordStorageWriteDuration(int $nanoseconds): void
    {
        if ($nanoseconds > 0) {
            $this->storageWriteNs += $nanoseconds;
        }

        if ($this->active) {
            ++$this->storageWrites;
        }
    }

    public function recordSessionLockDuration(int $nanoseconds): void
    {
        if ($nanoseconds > 0) {
            $this->sessionLockNs += $nanoseconds;
        }
    }

    public function markSessionActive(): void
    {
        if ($this->sessionActiveSinceNs === null) {
            $this->sessionActiveSinceNs = hrtime(true);
        }
    }

    public function recordSessionReleased(): void
    {
        if ($this->sessionActiveSinceNs === null) {
            return;
        }

        $this->sessionHeldNs += hrtime(true) - $this->sessionActiveSinceNs;
        $this->sessionActiveSinceNs = null;
    }

    public function storageReads(): int
    {
        return $this->storageReads;
    }

    public function storageWrites(): int
    {
        return $this->storageWrites;
    }

    public function storageMs(): float
    {
        return round(($this->storageReadNs + $this->storageWriteNs) / 1_000_000, 2);
    }

    public function sessionLockMs(): float
    {
        return round($this->sessionLockNs / 1_000_000, 2);
    }

    public function sessionHeldMs(): float
    {
        $heldNs = $this->sessionHeldNs;

        if ($this->sessionActiveSinceNs !== null) {
            $heldNs += hrtime(true) - $this->sessionActiveSinceNs;
        }

        return round($heldNs / 1_000_000, 2);
    }
}
