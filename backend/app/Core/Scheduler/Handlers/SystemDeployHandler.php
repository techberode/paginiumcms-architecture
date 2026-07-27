<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler\Handlers;

use PaginiumCMS\Core\Scheduler\Contracts\JobHandlerInterface;
use PaginiumCMS\Core\Scheduler\Models\JobRunResult;
use PaginiumCMS\Core\SystemUpdate\Services\SystemDeployService;

final class SystemDeployHandler implements JobHandlerInterface
{
    public function __construct(private SystemDeployService $deploy)
    {
    }

    public function key(): string
    {
        return 'system.deploy';
    }

    public function label(): string
    {
        return 'System code deploy';
    }

    public function handle(array $payload = []): JobRunResult
    {
        return $this->deploy->deploy($payload);
    }
}
