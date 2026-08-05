<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Git\Services;

use PaginiumCMS\Core\Git\Contracts\GitPublisherInterface;
use PaginiumCMS\Core\Git\Models\GitPublishResult;
use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;
use PaginiumCMS\Support\LogSanitizer;
use RuntimeException;

/**
 * Orchestrates Git distribution after SSOT writes (Iteration 70).
 */
final class GitPublishService
{
    public function __construct(
        private GitPublishSettings $gitSettings,
        private PublishQueueStore $queue,
        private PublishPlanner $planner,
        private LocalGitPublisher $localPublisher,
        private GitPathValidator $paths,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        $publisherStatus = [];
        if ($this->gitSettings->isActive() && $this->gitSettings->publisher() === 'local') {
            try {
                $publisherStatus = $this->localPublisher->status();
            } catch (\Throwable $e) {
                $publisherStatus = [
                    'publisher' => 'local',
                    'repositoryConfigured' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        $pending = $this->queue->pending();

        return [
            'enabled' => $this->gitSettings->isEnabled(),
            'strategy' => $this->gitSettings->strategy(),
            'publisher' => $this->gitSettings->publisher(),
            'pendingCount' => count($pending),
            'pending' => array_map(static fn ($item) => $item->toArray(), $pending),
            'publisherStatus' => $publisherStatus,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function previewRelease(): array
    {
        $plan = $this->planner->planRelease($this->queue->pending());

        return [
            'strategy' => $this->gitSettings->strategy(),
            'pathCount' => count($plan['paths']),
            'paths' => $plan['paths'],
            'message' => $plan['message'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function publishRelease(?string $actorEmail = null): array
    {
        if ($this->gitSettings->strategy() !== 'queued') {
            throw new RuntimeException('Release publish is only available in queued strategy.');
        }

        return $this->executePlan($this->planner->planRelease($this->queue->pending()), $actorEmail);
    }

    public function afterContentStored(string $contentPath, string $fingerprint): ?GitPublishResult
    {
        if (!$this->gitSettings->isActive()) {
            return null;
        }

        $relativePath = $this->paths->normalizeContentPath($contentPath);
        $strategy = $this->gitSettings->strategy();

        if ($strategy === 'queued') {
            $this->queue->enqueue($relativePath, $fingerprint);

            return new GitPublishResult(true, 'pending_publish', null, 'Queued for Git release');
        }

        if ($strategy === 'immediate') {
            $result = $this->executePlan([
                'paths' => [$relativePath],
                'message' => $this->planner->messageForCount(1),
                'itemIds' => [],
            ], null);

            return new GitPublishResult(
                (bool) ($result['success'] ?? false),
                (string) ($result['state'] ?? 'publish_failed'),
                isset($result['commitHash']) ? (string) $result['commitHash'] : null,
                isset($result['message']) ? (string) $result['message'] : null,
                isset($result['error']) ? (string) $result['error'] : null,
            );
        }

        return null;
    }

    /**
     * @param array{paths: list<string>, message: string, itemIds: list<string>} $plan
     * @return array<string, mixed>
     */
    private function executePlan(array $plan, ?string $actorEmail): array
    {
        if ($plan['paths'] === []) {
            return [
                'success' => true,
                'state' => 'stored',
                'message' => 'No pending Git changes',
            ];
        }

        $publisher = $this->resolvePublisher();

        try {
            $publisher->stage($plan['paths']);
            $commit = $publisher->commit($plan['message']);
            if (!$commit->success) {
                $this->auditFailure($actorEmail, $plan, $commit->error ?? 'commit failed');

                return array_merge(['success' => false], $commit->toArray());
            }

            $push = $publisher->push();
            $finalState = $push->state;
            if (!$push->success) {
                $this->auditFailure($actorEmail, $plan, $push->error ?? 'push failed');

                return [
                    'success' => false,
                    'state' => 'publish_failed',
                    'commitHash' => $commit->commitHash,
                    'error' => $push->error,
                ];
            }

            if ($plan['itemIds'] !== []) {
                $this->queue->markCommitted($plan['itemIds'], (string) $commit->commitHash);
            }

            $this->logger->info('Git publish completed', LogSanitizer::context([
                'actor' => $actorEmail ?? 'system',
                'strategy' => $this->gitSettings->strategy(),
                'count' => count($plan['paths']),
                'commit' => $commit->commitHash,
                'state' => $finalState,
            ]));

            return [
                'success' => true,
                'state' => $finalState,
                'commitHash' => $commit->commitHash,
                'count' => count($plan['paths']),
            ];
        } catch (\Throwable $e) {
            $this->auditFailure($actorEmail, $plan, $e->getMessage());

            return [
                'success' => false,
                'state' => 'publish_failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @param array{paths: list<string>, message: string, itemIds: list<string>} $plan
     */
    private function auditFailure(?string $actorEmail, array $plan, string $error): void
    {
        $this->logger->warning('Git publish failed', LogSanitizer::context([
            'actor' => $actorEmail ?? 'system',
            'strategy' => $this->gitSettings->strategy(),
            'count' => count($plan['paths']),
            'error' => $error,
        ]));
    }

    private function resolvePublisher(): GitPublisherInterface
    {
        if ($this->gitSettings->publisher() !== 'local') {
            throw new RuntimeException('Only local Git publisher is available in this release.');
        }

        return $this->localPublisher;
    }
}
