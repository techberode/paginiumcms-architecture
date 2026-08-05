<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Git\Services\GitPublishService;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Security\Models\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * Admin Git distribution API (Iteration 70).
 */
final class GitPublishController
{
    public function __construct(
        private GitPublishService $gitPublish,
        private JsonResponder $json,
    ) {
    }

    public function status(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, $this->gitPublish->status());
    }

    public function preview(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, $this->gitPublish->previewRelease());
    }

    public function publish(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $actor = $this->actorEmail($request);
            $result = $this->gitPublish->publishRelease($actor);

            return $this->json->success(
                $response,
                $result,
                ($result['success'] ?? false) ? 200 : 422,
                ($result['success'] ?? false) ? 'Git release published' : 'Git publish failed'
            );
        } catch (RuntimeException $e) {
            return $this->json->error($response, $e->getMessage(), 422);
        }
    }

    /**
     * @param array<string, string> $args
     */
    public function retry(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        // Retry reuses release publish for queued batch (idempotent planner collapses duplicates).
        return $this->publish($request, $response);
    }

    private function actorEmail(ServerRequestInterface $request): ?string
    {
        $user = $request->getAttribute('user');
        if ($user instanceof User) {
            return $user->getEmail();
        }

        return null;
    }
}
