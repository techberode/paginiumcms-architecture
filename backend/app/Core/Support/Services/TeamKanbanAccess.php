<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Support\Services;

use InvalidArgumentException;
use PaginiumCMS\Core\Teams\Services\TeamRepository;
use PaginiumCMS\Modules\Security\Models\User;

/**
 * Team-scoped Kanban access (members read/write tickets; leaders manage board settings).
 */
final class TeamKanbanAccess
{
    public function __construct(
        private TeamRepository $teams,
    ) {
    }

    /**
     * @return list<array{id: string, name: string, color: string, type: string, canManageBoard: bool}>
     */
    public function listAccessibleTeams(User $actor): array
    {
        $out = [];
        foreach ($this->teams->list() as $team) {
            if (!$this->canAccess($team, $actor)) {
                continue;
            }
            $out[] = [
                'id' => (string) $team['id'],
                'name' => (string) $team['name'],
                'color' => $this->teamColor($team),
                'type' => (string) ($team['type'] ?? ''),
                'canManageBoard' => $this->canManageBoard($team, $actor),
            ];
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public function requireAccess(string $teamId, User $actor): array
    {
        $team = $this->teams->get($teamId);
        if ($team === null || !$this->canAccess($team, $actor)) {
            throw new InvalidArgumentException('Kanban is not available for this team.');
        }

        return $team;
    }

    /**
     * @param array<string, mixed> $team
     */
    public function canAccess(array $team, User $actor): bool
    {
        if (!$this->kanbanEnabled($team)) {
            return false;
        }
        if ($actor->hasRole('SUPER_ADMIN')) {
            return true;
        }
        $members = is_array($team['memberUserIds'] ?? null) ? $team['memberUserIds'] : [];

        return in_array($actor->getId(), $members, true);
    }

    /**
     * @param array<string, mixed> $team
     */
    public function canManageBoard(array $team, User $actor): bool
    {
        if ($actor->hasRole('SUPER_ADMIN')) {
            return true;
        }
        $leaders = is_array($team['teamLeaderUserIds'] ?? null) ? $team['teamLeaderUserIds'] : [];

        return in_array($actor->getId(), $leaders, true);
    }

    /**
     * @param array<string, mixed> $team
     */
    public function kanbanEnabled(array $team): bool
    {
        if (array_key_exists('kanbanEnabled', $team)) {
            return (bool) $team['kanbanEnabled'];
        }

        return ($team['type'] ?? '') === TeamRepository::TYPE_SUPPORT;
    }

    /**
     * @param array<string, mixed> $team
     */
    public function teamColor(array $team): string
    {
        $color = is_string($team['color'] ?? null) ? strtolower(trim($team['color'])) : '';

        return preg_match('/^#[0-9a-f]{6}$/', $color) === 1 ? $color : '#2563eb';
    }
}
