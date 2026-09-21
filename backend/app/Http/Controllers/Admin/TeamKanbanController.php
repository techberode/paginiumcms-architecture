<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use InvalidArgumentException;
use PaginiumCMS\Core\Support\Services\SupportKanbanRepository;
use PaginiumCMS\Core\Support\Services\TeamKanbanAccess;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Per-team Kanban — access by team membership when {@see TeamKanbanAccess::kanbanEnabled}.
 */
final class TeamKanbanController
{
    public function __construct(
        private SupportKanbanRepository $kanban,
        private TeamKanbanAccess $access,
        private UserRepository $users,
        private JsonResponder $json,
    ) {
    }

    public function teams(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $actor = $request->getAttribute('user');
        if (!$actor instanceof User) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        return $this->json->success($response, ['teams' => $this->access->listAccessibleTeams($actor)]);
    }

    /**
     * @param array<string, string> $args
     */
    public function index(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $actor = $request->getAttribute('user');
        if (!$actor instanceof User) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        $teamId = (string) ($args['teamId'] ?? '');
        try {
            $team = $this->access->requireAccess($teamId, $actor);
        } catch (InvalidArgumentException $exception) {
            return $this->json->error($response, $exception->getMessage(), 403);
        }

        $board = $this->kanban->getBoard($teamId);
        $payload = [
            'team' => [
                'id' => (string) $team['id'],
                'name' => (string) $team['name'],
                'color' => $this->access->teamColor($team),
                'canManageBoard' => $this->access->canManageBoard($team, $actor),
            ],
            'board' => $board,
            'tickets' => $this->kanban->listTickets($teamId),
            'agents' => $this->agents($team),
            'cannedReplies' => $this->kanban->getCannedReplies($teamId)['replies'],
        ];
        if (($board['statsEnabled'] ?? false) === true) {
            $payload['stats'] = $this->kanban->stats($teamId);
        }

        return $this->json->success($response, $payload);
    }

    /**
     * @param array<string, string> $args
     */
    public function saveCanned(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $denied = $this->requireBoardManage($request, $response, $args);
        if ($denied !== null) {
            return $denied;
        }

        $teamId = (string) ($args['teamId'] ?? '');
        $body = RequestJsonBody::decode($request) ?? [];

        try {
            $canned = $this->kanban->saveCannedReplies($teamId, $body);
        } catch (InvalidArgumentException $exception) {
            return $this->json->validation($response, 'Validation failed', ['canned' => $exception->getMessage()]);
        }

        return $this->json->success($response, ['cannedReplies' => $canned['replies']]);
    }

    /**
     * @param array<string, string> $args
     */
    public function saveBoard(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $denied = $this->requireBoardManage($request, $response, $args);
        if ($denied !== null) {
            return $denied;
        }

        $teamId = (string) ($args['teamId'] ?? '');
        $body = RequestJsonBody::decode($request) ?? [];

        try {
            $board = $this->kanban->saveBoard($teamId, $body);
        } catch (InvalidArgumentException $exception) {
            return $this->json->validation($response, 'Validation failed', ['board' => $exception->getMessage()]);
        }

        return $this->json->success($response, ['board' => $board]);
    }

    /**
     * @param array<string, string> $args
     */
    public function storeTicket(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $team = $this->requireMember($request, $response, $args);
        if ($team instanceof ResponseInterface) {
            return $team;
        }

        $teamId = (string) ($args['teamId'] ?? '');
        $body = RequestJsonBody::decode($request) ?? [];

        try {
            $ticket = $this->kanban->createTicket($teamId, $body, $this->agentIds($team));
        } catch (InvalidArgumentException $exception) {
            return $this->json->validation($response, 'Validation failed', ['ticket' => $exception->getMessage()]);
        }

        return $this->json->success($response, ['ticket' => $ticket], 201);
    }

    /**
     * @param array<string, string> $args
     */
    public function updateTicket(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $team = $this->requireMember($request, $response, $args);
        if ($team instanceof ResponseInterface) {
            return $team;
        }

        $teamId = (string) ($args['teamId'] ?? '');
        $body = RequestJsonBody::decode($request) ?? [];

        try {
            $ticket = $this->kanban->updateTicket(
                $teamId,
                (string) ($args['id'] ?? ''),
                $body,
                $this->agentIds($team)
            );
        } catch (InvalidArgumentException $exception) {
            if ($exception->getMessage() === 'Ticket not found') {
                return $this->json->error($response, $exception->getMessage(), 404);
            }

            return $this->json->validation($response, 'Validation failed', ['ticket' => $exception->getMessage()]);
        }

        return $this->json->success($response, ['ticket' => $ticket]);
    }

    /**
     * @param array<string, string> $args
     */
    public function destroyTicket(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $team = $this->requireMember($request, $response, $args);
        if ($team instanceof ResponseInterface) {
            return $team;
        }

        $teamId = (string) ($args['teamId'] ?? '');
        try {
            $this->kanban->deleteTicket($teamId, (string) ($args['id'] ?? ''));
        } catch (InvalidArgumentException $exception) {
            return $this->json->error($response, $exception->getMessage(), 404);
        }

        return $this->json->success($response, ['removed' => true]);
    }

    /**
     * @param array<string, string> $args
     */
    public function addNote(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $team = $this->requireMember($request, $response, $args);
        if ($team instanceof ResponseInterface) {
            return $team;
        }

        $user = $request->getAttribute('user');
        if (!$user instanceof User) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        $teamId = (string) ($args['teamId'] ?? '');
        $body = RequestJsonBody::decode($request) ?? [];
        $text = is_string($body['body'] ?? null) ? $body['body'] : '';

        try {
            $ticket = $this->kanban->addInternalNote(
                $teamId,
                (string) ($args['id'] ?? ''),
                $text,
                $user->getId()
            );
        } catch (InvalidArgumentException $exception) {
            if ($exception->getMessage() === 'Ticket not found') {
                return $this->json->error($response, $exception->getMessage(), 404);
            }

            return $this->json->validation($response, 'Validation failed', ['note' => $exception->getMessage()]);
        }

        return $this->json->success($response, ['ticket' => $ticket], 201);
    }

    /**
     * @param array<string, string> $args
     * @return array<string, mixed>|ResponseInterface
     */
    private function requireMember(ServerRequestInterface $request, ResponseInterface $response, array $args): array|ResponseInterface
    {
        $actor = $request->getAttribute('user');
        if (!$actor instanceof User) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        try {
            return $this->access->requireAccess((string) ($args['teamId'] ?? ''), $actor);
        } catch (InvalidArgumentException $exception) {
            return $this->json->error($response, $exception->getMessage(), 403);
        }
    }

    /**
     * @param array<string, string> $args
     */
    private function requireBoardManage(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ?ResponseInterface {
        $actor = $request->getAttribute('user');
        if (!$actor instanceof User) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        try {
            $team = $this->access->requireAccess((string) ($args['teamId'] ?? ''), $actor);
        } catch (InvalidArgumentException $exception) {
            return $this->json->error($response, $exception->getMessage(), 403);
        }

        if (!$this->access->canManageBoard($team, $actor)) {
            return $this->json->error($response, 'Board settings require a team leader.', 403);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $team
     * @return list<string>
     */
    private function agentIds(array $team): array
    {
        $raw = $team['memberUserIds'] ?? [];
        if (!is_array($raw)) {
            return [];
        }
        $ids = [];
        foreach ($raw as $id) {
            if (is_string($id) && $id !== '') {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * @param array<string, mixed> $team
     * @return list<array{id: string, name: string, email: string}>
     */
    private function agents(array $team): array
    {
        $items = [];
        foreach ($this->agentIds($team) as $userId) {
            $user = $this->users->findById($userId);
            if (!$user instanceof User) {
                continue;
            }
            $items[] = [
                'id' => $user->getId(),
                'name' => $user->getName() !== '' ? $user->getName() : $user->getUsername(),
                'email' => $user->getEmail(),
            ];
        }

        return $items;
    }
}
