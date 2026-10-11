<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler\Handlers;

use PaginiumCMS\Core\Cache\Contracts\AdminOverviewCacheWarmRunner;
use PaginiumCMS\Core\Scheduler\Contracts\JobHandlerInterface;
use PaginiumCMS\Core\Scheduler\Models\JobRunResult;

final class AdminOverviewCacheWarmHandler implements JobHandlerInterface
{
    public function __construct(private AdminOverviewCacheWarmRunner $warmer)
    {
    }

    public function key(): string
    {
        return 'admin_cache.warm';
    }

    public function label(): string
    {
        return 'Warm admin P2 overview cache (It.100c)';
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handle(array $payload = []): JobRunResult
    {
        $segments = $this->warmer->warmAll();

        return new JobRunResult(
            true,
            'Warmed: ' . implode(', ', $segments),
            ['segments' => $segments]
        );
    }
}
