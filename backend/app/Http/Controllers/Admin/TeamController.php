<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use InvalidArgumentException;
use PaginiumCMS\Core\Mail\Services\SiteMailboxGuard;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Teams\Services\TeamRepository;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Admin teams (It.93k). Read: ADMIN+; create/update/delete (incl. members): SUPER_ADMIN only.
 */
final class TeamController
{
    public function __construct(
        private TeamRepository $teams,
        private UserRepository $users,
        private JsonResponder $json,
        private SettingsRepositoryInterface $settings,
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $type = is_string($query['type'] ?? null) ? trim($query['type']) : '';

        try {
            $records = $this->teams->list($type !== '' ? $type : null);
        } catch (InvalidArgumentException $exception) {
            return $this->json->validation($response, 'Validation failed', ['type' => $exception->getMessage()]);
        }

        $teams = [];
        foreach ($records as $record) {
            $teams[] = $this->present($record);
        }

        return $this->json->success($response, [
            'teams' => $teams,
            'types' => $this->teams->types(),
            'users' => $this->pickerUsers(),
        ]);
    }

    /**
     * @param array<string, string> $args
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $team = $this->teams->get((string) ($args['id'] ?? ''));
        if ($team === null) {
            return $this->json->error($response, 'Team not found', 404);
        }

        return $this->json->success($response, ['team' => $this->present($team)]);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($deny = $this->denyUnlessSuperAdmin($request, $response)) {
            return $deny;
        }
        $body = RequestJsonBody::decode($request) ?? [];
        $name = is_string($body['name'] ?? null) ? $body['name'] : '';
        $type = is_string($body['type'] ?? null) ? $body['type'] : TeamRepository::TYPE_CUSTOM;

        try {
            $memberIds = $this->acceptedMemberIds($body['memberUserIds'] ?? []);
            $team = $this->teams->create($name, $type, $memberIds);
            $extras = [];
            if (array_key_exists('replyMailEnabled', $body)) {
                $extras['replyMailEnabled'] = (bool) $body['replyMailEnabled'];
            }
            if (array_key_exists('replyMail', $body)) {
                $extras['replyMail'] = strtolower(trim((string) $body['replyMail']));
            }
            if (array_key_exists('color', $body)) {
                $extras['color'] = is_string($body['color']) ? $body['color'] : '';
            }
            if (array_key_exists('chatEnabled', $body)) {
                $extras['chatEnabled'] = (bool) $body['chatEnabled'];
            }
            if (array_key_exists('kanbanEnabled', $body)) {
                $extras['kanbanEnabled'] = (bool) $body['kanbanEnabled'];
            }
            if (array_key_exists('teamChatEnabled', $body)) {
                $extras['teamChatEnabled'] = (bool) $body['teamChatEnabled'];
            }
            if (array_key_exists('teamChatShareEnabled', $body)) {
                $extras['teamChatShareEnabled'] = (bool) $body['teamChatShareEnabled'];
            }
            if (array_key_exists('teamChatShareWithTeamIds', $body)) {
                $extras['teamChatShareWithTeamIds'] = is_array($body['teamChatShareWithTeamIds'])
                    ? $body['teamChatShareWithTeamIds']
                    : [];
            }
            if (array_key_exists('teamLeaderUserIds', $body)) {
                $extras['teamLeaderUserIds'] = is_array($body['teamLeaderUserIds']) ? $body['teamLeaderUserIds'] : [];
            }
            if ($extras !== []) {
                $this->assertReplyMailbox((string) $team['id'], $extras);
                $team = $this->teams->update((string) $team['id'], $extras);
            }
        } catch (InvalidArgumentException $exception) {
            return $this->json->validation($response, 'Validation failed', ['team' => $exception->getMessage()]);
        }

        return $this->json->success($response, ['team' => $this->present($team)], 201);
    }

    /**
     * @param array<string, string> $args
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if ($deny = $this->denyUnlessSuperAdmin($request, $response)) {
            return $deny;
        }
        $id = (string) ($args['id'] ?? '');
        $body = RequestJsonBody::decode($request) ?? [];
        $payload = [];
        if (array_key_exists('name', $body)) {
            $payload['name'] = is_string($body['name']) ? $body['name'] : '';
        }
        if (array_key_exists('type', $body)) {
            $payload['type'] = is_string($body['type']) ? $body['type'] : '';
        }
        if (array_key_exists('memberUserIds', $body)) {
            try {
                $payload['memberUserIds'] = $this->acceptedMemberIds($body['memberUserIds']);
            } catch (InvalidArgumentException $exception) {
                return $this->json->validation($response, 'Validation failed', ['members' => $exception->getMessage()]);
            }
        }
        if (array_key_exists('chatEnabled', $body)) {
            $payload['chatEnabled'] = (bool) $body['chatEnabled'];
        }
        if (array_key_exists('kanbanEnabled', $body)) {
            $payload['kanbanEnabled'] = (bool) $body['kanbanEnabled'];
        }
        if (array_key_exists('teamChatEnabled', $body)) {
            $payload['teamChatEnabled'] = (bool) $body['teamChatEnabled'];
        }
        if (array_key_exists('teamChatShareEnabled', $body)) {
            $payload['teamChatShareEnabled'] = (bool) $body['teamChatShareEnabled'];
        }
        if (array_key_exists('teamChatShareWithTeamIds', $body)) {
            $payload['teamChatShareWithTeamIds'] = is_array($body['teamChatShareWithTeamIds'])
                ? $body['teamChatShareWithTeamIds']
                : [];
        }
        if (array_key_exists('replyMailEnabled', $body)) {
            $payload['replyMailEnabled'] = (bool) $body['replyMailEnabled'];
        }
        if (array_key_exists('replyMail', $body)) {
            $payload['replyMail'] = strtolower(trim((string) $body['replyMail']));
        }
        if (array_key_exists('color', $body)) {
            $payload['color'] = is_string($body['color']) ? $body['color'] : '';
        }
        if (array_key_exists('teamLeaderUserIds', $body)) {
            $payload['teamLeaderUserIds'] = is_array($body['teamLeaderUserIds']) ? $body['teamLeaderUserIds'] : [];
        }

        try {
            $this->assertReplyMailbox($id, $payload);
            $team = $this->teams->update($id, $payload);
        } catch (InvalidArgumentException $exception) {
            if ($exception->getMessage() === 'Team not found') {
                return $this->json->error($response, $exception->getMessage(), 404);
            }

            return $this->json->validation($response, 'Validation failed', ['team' => $exception->getMessage()]);
        }

        return $this->json->success($response, ['team' => $this->present($team)]);
    }

    /**
     * @param array<string, string> $args
     */
    public function destroy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if ($deny = $this->denyUnlessSuperAdmin($request, $response)) {
            return $deny;
        }
        try {
            $this->teams->delete((string) ($args['id'] ?? ''));
        } catch (InvalidArgumentException $exception) {
            if ($exception->getMessage() === 'Team not found') {
                return $this->json->error($response, $exception->getMessage(), 404);
            }

            return $this->json->validation($response, 'Validation failed', ['id' => $exception->getMessage()]);
        }

        return $this->json->success($response, ['removed' => true]);
    }

    /**
     * @param array<string, mixed> $team
     * @return array<string, mixed>
     */
    private function present(array $team): array
    {
        $ids = is_array($team['memberUserIds'] ?? null) ? $team['memberUserIds'] : [];
        $members = [];
        $kept = [];
        foreach ($ids as $id) {
            if (!is_string($id)) {
                continue;
            }
            $user = $this->users->findById($id);
            if ($user === null) {
                continue;
            }
            $kept[] = $user->getId();
            $members[] = $this->presentUser($user);
        }

        $team['memberUserIds'] = $kept;
        $team['members'] = $members;

        return $team;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function pickerUsers(): array
    {
        $items = [];
        foreach ($this->users->findAll() as $user) {
            if (!$user instanceof User) {
                continue;
            }
            $items[] = $this->presentUser($user);
        }

        usort(
            $items,
            static fn (array $a, array $b): int => strcmp((string) $a['name'], (string) $b['name'])
        );

        return $items;
    }

    /**
     * @return array{id: string, name: string, username: string, email: string, active: bool, avatarUrl: ?string}
     */
    private function presentUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'name' => $user->getName() !== '' ? $user->getName() : $user->getUsername(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'active' => $user->isActive(),
            'avatarUrl' => $user->getAvatarUrl(),
        ];
    }

    /**
     * @param mixed $raw
     * @return list<string>
     */
    private function acceptedMemberIds(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $unknown = [];
        $kept = [];
        foreach ($raw as $value) {
            if (!is_string($value)) {
                continue;
            }
            $id = trim($value);
            if ($id === '') {
                continue;
            }
            if ($this->users->findById($id) === null) {
                $unknown[] = $id;
                continue;
            }
            $kept[] = $id;
        }

        if ($unknown !== []) {
            throw new InvalidArgumentException('Unknown member user id.');
        }

        return $kept;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function assertReplyMailbox(string $teamId, array $payload): void
    {
        $existing = $this->teams->get($teamId) ?? [];
        $enabled = array_key_exists('replyMailEnabled', $payload)
            ? (bool) $payload['replyMailEnabled']
            : (bool) ($existing['replyMailEnabled'] ?? false);
        if (!$enabled) {
            return;
        }
        $mailbox = array_key_exists('replyMail', $payload)
            ? (string) $payload['replyMail']
            : (string) ($existing['replyMail'] ?? '');
        $host = SiteMailboxGuard::siteHost((string) ($this->settings->group('general')['siteUrl'] ?? ''));
        SiteMailboxGuard::assertMailbox($mailbox, $host);
    }

    private function denyUnlessSuperAdmin(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ?ResponseInterface {
        $user = $request->getAttribute('user');
        if (!$user instanceof User || !$user->hasRole('SUPER_ADMIN')) {
            return $this->json->error($response, 'Only SUPER_ADMIN may manage teams.', 403);
        }

        return null;
    }
}
