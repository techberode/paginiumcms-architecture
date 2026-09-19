<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use InvalidArgumentException;
use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Teams\Services\TeamRepository;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Support\LogSanitizer;

/**
 * Applies a public registration type and optional welcome mail (It.93o-7).
 */
final class RegistrationService
{
    public function __construct(
        private RegistrationOptionsStore $options,
        private TeamRepository $teams,
        private UserRepository $users,
        private ?NotificationService $notifications = null,
    ) {
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    public function publicOptions(): array
    {
        return $this->options->publicList();
    }

    /**
     * @return array{roles: list<string>, active: bool, registrationOptionId: string, assignTeamId: string}
     */
    public function plan(?string $optionId): array
    {
        $optionId = trim((string) $optionId);
        $enabled = $this->options->publicList();
        if ($enabled === []) {
            return [
                'roles' => ['USER'],
                'active' => true,
                'registrationOptionId' => '',
                'assignTeamId' => '',
            ];
        }
        if ($optionId === '') {
            throw new InvalidArgumentException('Select a registration type.');
        }
        $option = $this->options->get($optionId);
        if ($option === null || ($option['enabled'] ?? false) !== true) {
            throw new InvalidArgumentException('Unknown registration type.');
        }

        return [
            'roles' => [(string) $option['roleId']],
            'active' => !((bool) ($option['requireAdminApproval'] ?? true)),
            'registrationOptionId' => (string) $option['id'],
            'assignTeamId' => (string) ($option['assignTeamId'] ?? ''),
        ];
    }

    public function apply(User $user, ?string $optionId): void
    {
        $plan = $this->plan($optionId);
        $user->setRoles($plan['roles']);
        $user->setActive($plan['active']);
        $user->setRegistrationOptionId($plan['registrationOptionId']);
        if ($plan['active'] && $plan['assignTeamId'] !== '') {
            $this->attachToTeam($user, $plan['assignTeamId']);
        }
    }

    public function approve(User $user): void
    {
        $user->setActive(true);
        $this->users->save($user);
        $this->sendWelcome($user);
    }

    public function attachIfAssigned(User $user, string $teamId): void
    {
        $teamId = trim($teamId);
        if ($teamId === '') {
            return;
        }
        $this->attachToTeam($user, $teamId);
    }

    public function sendWelcome(User $user): bool
    {
        if ($this->notifications === null || !$user->isActive()) {
            return false;
        }
        $option = $this->options->get($user->getRegistrationOptionId());
        if (is_array($option) && ($option['welcomeMailEnabled'] ?? true) !== true) {
            return false;
        }
        $subject = is_array($option) ? trim((string) ($option['welcomeMailSubject'] ?? '')) : '';
        $body = is_array($option) ? trim((string) ($option['welcomeMailBody'] ?? '')) : '';
        if ($subject === '') {
            $subject = 'Registration confirmed';
        }
        if ($body === '') {
            $body = 'Your registration is complete. You can sign in now.';
        }

        try {
            return $this->notifications->send(
                'email',
                $user->getEmail(),
                LogSanitizer::value($subject, 160),
                $body
            );
        } catch (\Throwable) {
            return false;
        }
    }

    private function attachToTeam(User $user, string $teamId): void
    {
        $team = $this->teams->get($teamId);
        if ($team === null || ($team['type'] ?? '') !== TeamRepository::TYPE_EXTERNAL) {
            return;
        }
        $ids = is_array($team['memberUserIds'] ?? null) ? $team['memberUserIds'] : [];
        if (in_array($user->getId(), $ids, true)) {
            return;
        }
        $ids[] = $user->getId();
        try {
            $this->teams->update($teamId, ['memberUserIds' => $ids]);
        } catch (\Throwable) {
            // membership is best-effort after a valid registration
        }
    }
}
