<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Performance;

/**
 * Request-local counters for Performance Guard (Iteration 71).
 */
final class PerformanceContext
{
    private bool $active = false;
    private int $storageReads = 0;
    private int $storageWrites = 0;

    public function reset(): void
    {
        $this->active = true;
        $this->storageReads = 0;
        $this->storageWrites = 0;
    }

    public function deactivate(): void
    {
        $this->active = false;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function recordStorageRead(): void
    {
        if ($this->active) {
            ++$this->storageReads;
        }
    }

    public function recordStorageWrite(): void
    {
        if ($this->active) {
            ++$this->storageWrites;
        }
    }

    public function storageReads(): int
    {
        return $this->storageReads;
    }

    public function storageWrites(): int
    {
        return $this->storageWrites;
    }
}
