<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Agent\Exception\AgentException;
use PaginiumCMS\Core\Agent\Services\AgentService;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Security\Models\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * CMS-aware AI assistant API (It.75). Apply is a separate mutation.
 */
final class AgentController
{
    public function __construct(
        private AgentService $agent,
        private JsonResponder $json,
    ) {
    }

    public function status(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, $this->agent->status());
    }

    public function testConnection(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            return $this->json->success($response, $this->agent->testConnection());
        } catch (AgentException $e) {
            return $this->json->error($response, $e->getMessage(), $e->httpStatus, ['code' => $e->errorCode]);
        }
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $body = $request->getParsedBody();
            $run = $this->agent->enqueue(is_array($body) ? $body : [], $this->actor($request));

            return $this->json->success($response, $run, 202, 'Agent run queued');
        } catch (AgentException $e) {
            return $this->json->error($response, $e->getMessage(), $e->httpStatus, ['code' => $e->errorCode]);
        }
    }

    /**
     * @param array<string, string> $args
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            return $this->json->success(
                $response,
                $this->agent->show((string) ($args['runId'] ?? ''), $this->actor($request))
            );
        } catch (AgentException $e) {
            return $this->json->error($response, $e->getMessage(), $e->httpStatus, ['code' => $e->errorCode]);
        }
    }

    /**
     * @param array<string, string> $args
     */
    public function execute(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $run = $this->agent->show((string) ($args['runId'] ?? ''), $this->actor($request));

            return $this->json->success($response, $this->agent->execute((string) $run['id']));
        } catch (AgentException $e) {
            return $this->json->error($response, $e->getMessage(), $e->httpStatus, ['code' => $e->errorCode]);
        }
    }

    /**
     * @param array<string, string> $args
     */
    public function cancel(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $this->agent->cancel((string) ($args['runId'] ?? ''), $this->actor($request));

            return $this->json->success($response, ['cancelled' => true]);
        } catch (AgentException $e) {
            return $this->json->error($response, $e->getMessage(), $e->httpStatus, ['code' => $e->errorCode]);
        }
    }

    /**
     * @param array<string, string> $args
     */
    public function apply(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $result = $this->agent->apply((string) ($args['proposalId'] ?? ''), $this->actor($request));

            return $this->json->success($response, $result, 200, 'Agent proposal applied');
        } catch (AgentException $e) {
            return $this->json->error($response, $e->getMessage(), $e->httpStatus, ['code' => $e->errorCode]);
        }
    }

    /**
     * @param array<string, string> $args
     */
    public function discard(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $this->agent->discard((string) ($args['proposalId'] ?? ''), $this->actor($request));

            return $this->json->success($response, ['discarded' => true]);
        } catch (AgentException $e) {
            return $this->json->error($response, $e->getMessage(), $e->httpStatus, ['code' => $e->errorCode]);
        }
    }

    private function actor(ServerRequestInterface $request): User
    {
        $user = $request->getAttribute('user');
        if ($user instanceof User) {
            return $user;
        }

        throw new AgentException('Authentication required', 401, 'UNAUTHENTICATED');
    }
}
