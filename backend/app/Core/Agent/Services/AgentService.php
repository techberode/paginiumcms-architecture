<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Agent\Services;

use PaginiumCMS\Core\Agent\Contracts\AgentRunExecutorInterface;
use PaginiumCMS\Core\Agent\Contracts\LlmProviderInterface;
use PaginiumCMS\Core\Agent\Exception\AgentException;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PaginiumCMS\Support\LogSanitizer;

/**
 * Facade: enqueue (HTTP returns immediately) + execute (worker) + apply/discard (It.75).
 */
final class AgentService implements AgentRunExecutorInterface
{
    public function __construct(
        private AgentSettings $settings,
        private AgentRunStore $runs,
        private AgentProposalStore $proposals,
        private AgentBudgetStore $budget,
        private AgentOrchestrator $orchestrator,
        private AgentApplyService $apply,
        private AgentLlmProviderRegistry $providers,
        private UserRepository $users,
    ) {
    }

    /**
     * @return array{enabled: bool, provider: string, allowedTools: list<string>, quota: array{day: string, used: int, limit: int, remaining: int|null}}
     */
    public function status(): array
    {
        return [
            'enabled' => $this->settings->isActive(),
            'provider' => $this->settings->isActive() ? $this->settings->provider() : 'none',
            'model' => $this->settings->model(),
            'allowedTools' => $this->settings->allowedTools(),
            'quota' => $this->budget->snapshot(),
        ];
    }

    /**
     * @return array{ok: bool, provider: string, error?: string}
     */
    public function testConnection(): array
    {
        if (!$this->settings->isActive()) {
            throw new AgentException('Agent is disabled', 503, 'DISABLED');
        }

        $health = $this->providers->resolve()->health();
        $health['provider'] = $this->settings->provider();

        return $health;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function enqueue(array $payload, User $actor): array
    {
        if (!$this->settings->isEnabled()) {
            throw new AgentException('Agent is disabled', 503, 'DISABLED');
        }
        if (!$this->settings->isActive()) {
            throw new AgentException('Agent provider is not configured', 503, 'DISABLED');
        }

        $requested = $payload['tools'] ?? $this->settings->allowedTools();
        if (!is_array($requested)) {
            throw new AgentException('Invalid tools', 422, 'INVALID');
        }
        $tools = [];
        foreach ($requested as $name) {
            if (is_string($name) && in_array($name, $this->settings->allowedTools(), true)) {
                $tools[] = $name;
            }
        }
        $tools = array_values(array_unique($tools));
        if ($tools === []) {
            throw new AgentException('No tools are allowed', 422, 'NO_TOOLS');
        }

        $this->proposals->purgeExpired();
        $run = $this->runs->create([
            'status' => 'queued',
            'actorUserId' => $actor->getId(),
            'actorEmail' => $actor->getEmail(),
            'resourceType' => strtolower(trim((string) ($payload['resourceType'] ?? ''))),
            'resourceId' => strtolower(trim((string) ($payload['resourceId'] ?? ''))),
            'locale' => strtolower(trim((string) ($payload['locale'] ?? ''))),
            'sourceRevision' => trim((string) ($payload['sourceRevision'] ?? '')),
            'prompt' => LogSanitizer::value(trim((string) ($payload['prompt'] ?? '')), 500),
            'allowedTools' => $tools,
            'proposalId' => null,
            'error' => null,
        ]);

        return $this->presentRun($run);
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(string $runId, ?LlmProviderInterface $provider = null): array
    {
        $run = $this->runs->getUnchecked($runId);
        if (($run['status'] ?? '') === 'cancelled') {
            throw new AgentException('Agent run was cancelled', 409, 'CANCELLED');
        }
        if (($run['status'] ?? '') === 'succeeded') {
            return $this->presentRun($run);
        }

        $actor = $this->users->findById((string) ($run['actorUserId'] ?? ''));
        if ($actor === null) {
            throw new AgentException('Actor no longer exists', 403, 'FORBIDDEN');
        }

        $run['status'] = 'running';
        $this->runs->save($run);

        try {
            $outcome = $this->orchestrator->run($run, $actor, $provider);
            $run['status'] = 'succeeded';
            $run['tokens'] = $outcome['tokens'];
            $run['steps'] = $outcome['steps'];
            $proposal = is_array($outcome['proposal'] ?? null) ? $outcome['proposal'] : null;
            $run['proposalId'] = is_array($proposal) ? (string) ($proposal['id'] ?? '') : null;
            $run['error'] = null;
            $this->runs->save($run);

            return $this->presentRun($run, $proposal);
        } catch (AgentException $e) {
            $run['status'] = 'failed';
            $run['error'] = $e->errorCode;
            $this->runs->save($run);
            throw $e;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function show(string $runId, User $actor): array
    {
        $run = $this->runs->get($runId, $actor->getId());
        $proposal = null;
        $proposalId = (string) ($run['proposalId'] ?? '');
        if ($proposalId !== '') {
            try {
                $proposal = $this->proposals->get($proposalId, $actor->getId());
            } catch (AgentException) {
                $proposal = null;
            }
        }

        return $this->presentRun($run, $proposal);
    }

    public function cancel(string $runId, User $actor): void
    {
        $run = $this->runs->get($runId, $actor->getId());
        if (($run['status'] ?? '') === 'succeeded') {
            throw new AgentException('Finished run cannot be cancelled', 409, 'CONFLICT');
        }
        $run['status'] = 'cancelled';
        $this->runs->save($run);
    }

    /**
     * @return array<string, mixed>
     */
    public function apply(string $proposalId, User $actor): array
    {
        return $this->apply->apply($proposalId, $actor);
    }

    public function discard(string $proposalId, User $actor): void
    {
        $this->proposals->get($proposalId, $actor->getId());
        $this->proposals->delete($proposalId);
    }

    /**
     * @param array<string, mixed> $run
     * @param array<string, mixed>|null $proposal
     * @return array<string, mixed>
     */
    private function presentRun(array $run, ?array $proposal = null): array
    {
        return [
            'id' => (string) ($run['id'] ?? ''),
            'status' => (string) ($run['status'] ?? 'queued'),
            'resourceType' => (string) ($run['resourceType'] ?? ''),
            'resourceId' => (string) ($run['resourceId'] ?? ''),
            'allowedTools' => is_array($run['allowedTools'] ?? null) ? $run['allowedTools'] : [],
            'provider' => $this->settings->provider(),
            'model' => $this->settings->model(),
            'tokens' => (int) ($run['tokens'] ?? 0),
            'steps' => (int) ($run['steps'] ?? 0),
            'error' => $run['error'] ?? null,
            'proposal' => $proposal,
        ];
    }
}
