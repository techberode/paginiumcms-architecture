<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler\Contracts;

use PaginiumCMS\Core\Scheduler\Models\JobRunResult;

/**
 * Executes a registered scheduled job handler (Iteration 29).
 */
interface JobHandlerInterface
{
    public function key(): string;

    public function label(): string;

    /**
     * @param array<string, mixed> $payload
     */
    public function handle(array $payload = []): JobRunResult;
}
