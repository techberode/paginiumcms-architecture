<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler\Handlers;

use PaginiumCMS\Core\Agent\Contracts\AgentRunExecutorInterface;
use PaginiumCMS\Core\Agent\Exception\AgentException;
use PaginiumCMS\Core\Scheduler\Contracts\JobHandlerInterface;
use PaginiumCMS\Core\Scheduler\Models\JobRunResult;

final class AgentRunHandler implements JobHandlerInterface
{
    public function __construct(
        private AgentRunExecutorInterface $agent,
    ) {
    }

    public function key(): string
    {
        return 'agent.run';
    }

    public function label(): string
    {
        return 'CMS AI assistant run';
    }

    public function handle(array $payload = []): JobRunResult
    {
        $runId = trim((string) ($payload['runId'] ?? ''));
        if ($runId === '') {
            return new JobRunResult(false, 'Missing runId', $payload, 'invalid_payload');
        }

        try {
            $result = $this->agent->execute($runId);

            return new JobRunResult(true, 'Agent run completed', [
                'runId' => $runId,
                'status' => $result['status'],
                'proposalId' => is_array($result['proposal'] ?? null) ? ($result['proposal']['id'] ?? null) : null,
            ]);
        } catch (AgentException $e) {
            return new JobRunResult(false, $e->getMessage(), ['runId' => $runId], $e->errorCode);
        }
    }
}
