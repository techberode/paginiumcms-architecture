<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use InvalidArgumentException;
use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Teams\Services\TeamRepository;
use PaginiumCMS\Core\Validation\VisitorEmailGuard;

/**
 * Issues one-time register links that work even when public registration is off (It.93o-8).
 */
final class RegistrationInviteService
{
    public function __construct(
        private RegistrationInviteStore $store,
        private UserRepository $users,
        private TeamRepository $teams,
        private VisitorEmailGuard $visitorEmail,
        private SettingsRepositoryInterface $settings,
        private ?NotificationService $notifications = null,
    ) {
    }

    /**
     * @return array{invite: array<string, mixed>, token: string, url: string, mailed: bool}
     */
    public function issue(string $email, string $teamId, string $createdBy, bool $sendMail, string $source = 'admin'): array
    {
        $email = $this->visitorEmail->normalize($email, 'contact');
        if ($this->users->findByEmail($email) !== null) {
            throw new InvalidArgumentException('A user with this e-mail already exists.');
        }
        $teamId = trim($teamId);
        if ($teamId !== '' && $this->teams->get($teamId) === null) {
            throw new InvalidArgumentException('Unknown team.');
        }
        $created = $this->store->create($email, $teamId, $createdBy, $source);
        $url = $this->inviteUrl((string) $created['token']);
        $mailed = false;
        if ($sendMail && $this->notifications !== null) {
            try {
                $mailed = $this->notifications->send(
                    'email',
                    $email,
                    'Registration invite',
                    "Use this one-time link to create your account. A site administrator must confirm it before you can sign in.\n\n" . $url
                );
            } catch (\Throwable) {
                $mailed = false;
            }
        }

        return [
            'invite' => $created['record'],
            'token' => (string) $created['token'],
            'url' => $url,
            'mailed' => $mailed,
        ];
    }

    /**
     * @return array{email: string, expiresAt: int, teamId: string}|null
     */
    public function peek(string $token): ?array
    {
        $record = $this->store->findUsableByToken($token);
        if ($record === null) {
            return null;
        }

        return [
            'email' => (string) $record['email'],
            'expiresAt' => (int) ($record['expiresAt'] ?? 0),
            'teamId' => (string) ($record['teamId'] ?? ''),
        ];
    }

    public function consume(string $token, string $email, string $userId): void
    {
        $this->store->consume($token, $email, $userId);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        return $this->store->list();
    }

    public function revoke(string $id): void
    {
        $this->store->revoke($id);
    }

    /**
     * Invite registration is always USER + inactive. Role and team stay with the superadmin.
     *
     * @return array{roles: list<string>, active: bool, registrationOptionId: string, assignTeamId: string, inviteToken: string}
     */
    public function plan(string $token, string $email): array
    {
        $peek = $this->peek($token);
        if ($peek === null || !$this->store->emailsMatch($peek['email'], $email)) {
            throw new InvalidArgumentException('Invite is invalid or expired.');
        }

        return [
            'roles' => ['USER'],
            'active' => false,
            'registrationOptionId' => '',
            'assignTeamId' => '',
            'inviteToken' => $token,
        ];
    }

    private function inviteUrl(string $token): string
    {
        $base = rtrim((string) ($this->settings->group('general')['siteUrl'] ?? ''), '/');
        $path = '/register?invite=' . rawurlencode($token);
        if ($base === '') {
            return $path;
        }

        return $base . $path;
    }
}
