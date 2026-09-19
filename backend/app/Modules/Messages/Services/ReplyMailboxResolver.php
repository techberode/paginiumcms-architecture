<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Messages\Services;

use PaginiumCMS\Core\Mail\Services\SiteMailboxGuard;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Teams\Services\TeamRepository;
use PaginiumCMS\Modules\Messages\Models\ContactMessage;
use PaginiumCMS\Modules\Security\Models\User;

/**
 * Picks a site-domain From address: operator mailbox first, then team central.
 */
final class ReplyMailboxResolver
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private TeamRepository $teams,
    ) {
    }

    public function siteHost(): string
    {
        $general = $this->settings->group('general');

        return SiteMailboxGuard::siteHost((string) ($general['siteUrl'] ?? ''));
    }

    /**
     * @return array{email: string, name: string}|null
     */
    public function resolve(User $actor, ?ContactMessage $message = null): ?array
    {
        $host = $this->siteHost();
        if ($host === '') {
            return null;
        }

        if ($actor->isDeskMailEnabled() && SiteMailboxGuard::isMailboxAllowed($actor->getEmail(), $host)) {
            $name = $actor->getName() !== '' ? $actor->getName() : $actor->getEmail();

            return ['email' => strtolower($actor->getEmail()), 'name' => $name];
        }

        foreach ($this->candidateTeams($actor, $message) as $team) {
            if (($team['replyMailEnabled'] ?? false) !== true) {
                continue;
            }
            $mailbox = strtolower(trim((string) ($team['replyMail'] ?? '')));
            if (!SiteMailboxGuard::isMailboxAllowed($mailbox, $host)) {
                continue;
            }
            $name = trim((string) ($team['name'] ?? ''));

            return ['email' => $mailbox, 'name' => $name !== '' ? $name : $mailbox];
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function candidateTeams(User $actor, ?ContactMessage $message): array
    {
        $ids = [];
        if ($message !== null) {
            foreach ($message->getAssigneeTeamIds() as $teamId) {
                $ids[] = $teamId;
            }
        }
        foreach ($this->teams->list() as $team) {
            $members = is_array($team['memberUserIds'] ?? null) ? $team['memberUserIds'] : [];
            if (in_array($actor->getId(), $members, true) && !in_array((string) $team['id'], $ids, true)) {
                $ids[] = (string) $team['id'];
            }
        }

        $out = [];
        foreach ($ids as $id) {
            $team = $this->teams->get($id);
            if ($team !== null) {
                $out[] = $team;
            }
        }

        return $out;
    }
}
