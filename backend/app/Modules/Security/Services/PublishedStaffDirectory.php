<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Core\Teams\Services\TeamRepository;
use PaginiumCMS\Modules\Security\Models\User;

/**
 * Opt-in public cards for Contact / Support (It.93o). Never dumps full user JSON.
 */
final class PublishedStaffDirectory
{
    public function __construct(
        private UserRepository $users,
        private ?TeamRepository $teams = null,
        private ?StaffPresenceStore $presence = null,
    ) {
    }

    /**
     * @return array{contacts: list<array<string, mixed>>, support: list<array<string, mixed>>, cards?: list<array<string, mixed>>}
     */
    public function publicLists(): array
    {
        $contacts = [];
        $support = [];

        foreach ($this->users->findAll() as $user) {
            if (!$user instanceof User || !$this->isListed($user)) {
                continue;
            }

            $card = $this->present($user);
            if ($card === null) {
                continue;
            }

            $publish = $user->getPublish();
            if ($publish['contact'] === true) {
                $contacts[] = $card;
            }
            if ($publish['support'] === true) {
                $support[] = $card;
            }
        }

        return [
            'contacts' => $contacts,
            'support' => $support,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function resolveCards(?string $userRef, ?string $type, ?string $teamId): array
    {
        $userRef = trim((string) $userRef);
        $type = strtolower(trim((string) $type));
        $teamId = trim((string) $teamId);

        if ($userRef !== '') {
            $user = $this->users->findById($userRef) ?? $this->users->findByEmail($userRef);
            if (!$user instanceof User || !$this->isListed($user)) {
                return [];
            }
            $card = $this->present($user);

            return $card !== null ? [$card] : [];
        }

        if ($teamId !== '' && $this->teams !== null) {
            $team = $this->teams->get($teamId);
            if ($team === null) {
                return [];
            }

            $members = $team['memberUserIds'] ?? [];

            return $this->cardsForMemberIds(is_array($members) ? array_values($members) : []);
        }

        if (in_array($type, ['editorial', 'ops', 'custom'], true) && $this->teams !== null) {
            return $this->cardsForMemberIds($this->teams->memberIdsForType($type));
        }

        $lists = $this->publicLists();
        if ($type === 'support') {
            return $lists['support'];
        }
        if ($type === 'contact') {
            return $lists['contacts'];
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function chatStatus(User $user): array
    {
        $presence = $this->presence?->snapshot($user->getId()) ?? ['online' => false, 'lastSeen' => 0];

        return [
            'chatEnabled' => $user->isChatEnabled(),
            'online' => $presence['online'],
            'lastSeen' => $presence['lastSeen'],
            'inSupportTeam' => $this->inTeamType($user->getId(), TeamRepository::TYPE_SUPPORT),
            'canPublicChat' => $this->canPublicChat($user),
        ];
    }

    public function canPublicChat(User $user): bool
    {
        return $this->isListed($user)
            && $user->isChatEnabled()
            && $this->teamAllowsChat($user->getId());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function present(User $user): ?array
    {
        $name = trim($user->getName());
        if ($name === '') {
            return null;
        }

        $publish = $user->getPublish();
        $card = [
            'id' => $user->getId(),
            'name' => $name,
            'jobTitle' => $user->getJobTitle(),
            'bio' => $user->getBio(),
            'avatarUrl' => $user->getAvatarUrl(),
            'chatEnabled' => $this->canPublicChat($user),
            'online' => $this->canPublicChat($user) && ($this->presence?->isOnline($user->getId()) ?? false),
        ];

        if ($publish['phone'] === true && $user->getPhone() !== '') {
            $card['phone'] = $user->getPhone();
        }
        if ($publish['email'] === true) {
            $card['email'] = $user->getEmail();
        }
        if ($publish['address'] === true) {
            $address = $user->getAddress();
            if (implode('', $address) !== '') {
                $card['address'] = $address;
            }
        }
        if ($publish['experience'] === true && $user->getExperience() !== []) {
            $card['experience'] = $user->getExperience();
        }
        if ($publish['education'] === true && $user->getEducation() !== []) {
            $card['education'] = $user->getEducation();
        }
        if ($publish['socials'] === true) {
            $socials = [];
            foreach ($user->getSocialAccounts() as $account) {
                if (!SocialAccountLinkProbe::isVerified($account)) {
                    continue;
                }

                $href = $account['directChat']
                    ? UserProfileFields::chatUrl($account['platform'], $account['url'])
                    : ($account['platform'] === 'email'
                        ? 'mailto:' . $account['url']
                        : $account['url']);
                if ($href === '') {
                    continue;
                }
                $socials[] = [
                    'platform' => $account['platform'],
                    'label' => $account['label'],
                    'url' => $href,
                    'directChat' => $account['directChat'],
                ];
            }
            if ($socials !== []) {
                $card['socials'] = $socials;
            }
        }

        return $card;
    }

    private function isListed(User $user): bool
    {
        if (!$user->isActive()) {
            return false;
        }

        $publish = $user->getPublish();

        return $publish['contact'] === true || $publish['support'] === true;
    }

    /**
     * @param list<mixed> $memberIds
     * @return list<array<string, mixed>>
     */
    private function cardsForMemberIds(array $memberIds): array
    {
        $cards = [];
        foreach ($memberIds as $memberId) {
            if (!is_string($memberId) || $memberId === '') {
                continue;
            }
            $user = $this->users->findById($memberId);
            if (!$user instanceof User || !$this->isListed($user)) {
                continue;
            }
            $card = $this->present($user);
            if ($card !== null) {
                $cards[] = $card;
            }
        }

        return $cards;
    }

    private function teamAllowsChat(string $userId): bool
    {
        if ($this->teams === null) {
            return true;
        }

        $memberships = 0;
        foreach ($this->teams->list() as $team) {
            $members = $team['memberUserIds'] ?? [];
            if (!is_array($members) || !in_array($userId, $members, true)) {
                continue;
            }
            $memberships++;
            if (($team['chatEnabled'] ?? false) === true) {
                return true;
            }
        }

        return $memberships === 0;
    }

    private function inTeamType(string $userId, string $type): bool
    {
        if ($this->teams === null) {
            return false;
        }

        return in_array($userId, $this->teams->memberIdsForType($type), true);
    }
}
