<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use InvalidArgumentException;
use PaginiumCMS\Core\Support\Services\SupportKanbanRepository;
use PaginiumCMS\Core\Teams\Services\TeamRepository;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Support Kanban (It.93l / 93l-2): board, tickets, canned replies, internal notes.
 * Permission `support-ticket:manage`.
 */
final class SupportKanbanController
{
    public function __construct(
        private SupportKanbanRepository $kanban,
        private TeamRepository $teams,
        private UserRepository $users,
        private JsonResponder $json,
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $scope = SupportKanbanRepository::LEGACY_SCOPE;
        $board = $this->kanban->getBoard($scope);
        $payload = [
            'board' => $board,
            'tickets' => $this->kanban->listTickets($scope),
            'agents' => $this->agents(),
            'cannedReplies' => $this->kanban->getCannedReplies($scope)['replies'],
        ];
        if (($board['statsEnabled'] ?? false) === true) {
            $payload['stats'] = $this->kanban->stats($scope);
        }

        return $this->json->success($response, $payload);
    }

    public function saveCanned(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = RequestJsonBody::decode($request) ?? [];

        try {
            $canned = $this->kanban->saveCannedReplies(SupportKanbanRepository::LEGACY_SCOPE, $body);
        } catch (InvalidArgumentException $exception) {
            return $this->json->validation($response, 'Validation failed', ['canned' => $exception->getMessage()]);
        }

        return $this->json->success($response, ['cannedReplies' => $canned['replies']]);
    }

    public function saveBoard(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = RequestJsonBody::decode($request) ?? [];

        try {
            $board = $this->kanban->saveBoard(SupportKanbanRepository::LEGACY_SCOPE, $body);
        } catch (InvalidArgumentException $exception) {
            return $this->json->validation($response, 'Validation failed', ['board' => $exception->getMessage()]);
        }

        return $this->json->success($response, ['board' => $board]);
    }

    public function storeTicket(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = RequestJsonBody::decode($request) ?? [];

        try {
            $ticket = $this->kanban->createTicket(SupportKanbanRepository::LEGACY_SCOPE, $body, $this->agentIds());
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
        $body = RequestJsonBody::decode($request) ?? [];

        try {
            $ticket = $this->kanban->updateTicket(
                SupportKanbanRepository::LEGACY_SCOPE,
                (string) ($args['id'] ?? ''),
                $body,
                $this->agentIds()
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
        try {
            $this->kanban->deleteTicket(SupportKanbanRepository::LEGACY_SCOPE, (string) ($args['id'] ?? ''));
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
        $user = $request->getAttribute('user');
        if (!$user instanceof User) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        $body = RequestJsonBody::decode($request) ?? [];
        $text = is_string($body['body'] ?? null) ? $body['body'] : '';

        try {
            $ticket = $this->kanban->addInternalNote(
                SupportKanbanRepository::LEGACY_SCOPE,
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
     * @return list<string>
     */
    private function agentIds(): array
    {
        return $this->teams->memberIdsForType(TeamRepository::TYPE_SUPPORT);
    }

    /**
     * @return list<array{id: string, name: string, email: string}>
     */
    private function agents(): array
    {
        $items = [];
        foreach ($this->agentIds() as $userId) {
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
