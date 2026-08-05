<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler\Handlers;

use PaginiumCMS\Core\Git\Services\GitPublishService;
use PaginiumCMS\Core\Scheduler\Contracts\JobHandlerInterface;
use PaginiumCMS\Core\Scheduler\Models\JobRunResult;

/**
 * Processes queued Git release commits (Iteration 70).
 */
final class GitPublishHandler implements JobHandlerInterface
{
    public function __construct(private GitPublishService $gitPublish)
    {
    }

    public function key(): string
    {
        return 'git.publish';
    }

    public function label(): string
    {
        return 'Git publish release';
    }

    public function handle(array $payload = []): JobRunResult
    {
        try {
            $result = $this->gitPublish->publishRelease(null);
        } catch (\Throwable $e) {
            return new JobRunResult(false, $e->getMessage(), [], 'strategy_or_config');
        }

        $success = (bool) ($result['success'] ?? false);

        return new JobRunResult(
            $success,
            (string) ($result['message'] ?? ($success ? 'Git publish completed' : 'Git publish failed')),
            $result,
            $success ? null : 'publish_failed'
        );
    }
}
