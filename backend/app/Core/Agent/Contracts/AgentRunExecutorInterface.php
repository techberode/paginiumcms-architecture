<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Agent\Contracts;

/**
 * Worker-facing execute surface (It.75). HTTP enqueue stays on AgentService.
 */
interface AgentRunExecutorInterface
{
    /**
     * @return array<string, mixed>
     */
    public function execute(string $runId, ?LlmProviderInterface $provider = null): array;
}
