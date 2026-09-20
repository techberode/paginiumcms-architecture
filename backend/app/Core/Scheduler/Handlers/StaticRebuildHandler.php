<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler\Handlers;

use PaginiumCMS\Core\Scheduler\Contracts\JobHandlerInterface;
use PaginiumCMS\Core\Scheduler\Models\JobRunResult;
use PaginiumCMS\Core\StaticSite\StaticSiteGenerator;

/**
 * Compiles the derived static tree without touching the Git publish queue (It.48).
 */
final class StaticRebuildHandler implements JobHandlerInterface
{
    public function __construct(private StaticSiteGenerator $generator)
    {
    }

    public function key(): string
    {
        return 'static.rebuild';
    }

    public function label(): string
    {
        return 'Static site rebuild';
    }

    public function handle(array $payload = []): JobRunResult
    {
        $scope = strtolower(trim((string) ($payload['scope'] ?? 'all')));

        try {
            if ($scope === 'page') {
                $result = $this->generator->rebuildOne(
                    (string) ($payload['type'] ?? 'page'),
                    (string) ($payload['slug'] ?? '')
                );
            } else {
                $result = $this->generator->rebuildAll();
            }
        } catch (\Throwable $e) {
            return new JobRunResult(false, $e->getMessage(), [], 'compile_failed');
        }

        return new JobRunResult(
            $result['success'],
            $result['message'],
            $result,
            $result['success'] ? null : 'compile_failed'
        );
    }
}
