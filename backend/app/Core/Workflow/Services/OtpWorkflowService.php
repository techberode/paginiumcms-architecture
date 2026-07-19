<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Workflow\Services;

use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use RuntimeException;

/**
 * Email OTP workflows (Iteration 41) — registration, comment approval, publish approval.
 */
final class OtpWorkflowService
{
    public const FLOW_REGISTRATION = 'registration';
    public const FLOW_COMMENT_APPROVAL = 'comment_approval';
    public const FLOW_PUBLISH_APPROVAL = 'publish_approval';

    public function __construct(
        private OtpChallengeStore $store,
        private SettingsRepositoryInterface $settings,
        private NotificationService $notifications,
        private UserRepository $users
    ) {
    }

    public function isRegistrationOtpEnabled(): bool
    {
        $workflows = $this->settings->group('workflows');

        return (bool) ($workflows['registrationOtpEnabled'] ?? false);
    }

    public function isCommentApprovalOtpEnabled(): bool
    {
        $workflows = $this->settings->group('workflows');

        return (bool) ($workflows['commentApprovalOtpEnabled'] ?? false);
    }

    public function isPublishApprovalOtpEnabled(): bool
    {
        $workflows = $this->settings->group('workflows');

        return (bool) ($workflows['publishApprovalOtpEnabled'] ?? false);
    }

    /**
     * @return array{challenge_id: string, expires_at: int, debug_code?: string}
     */
    public function startRegistration(string $email, string $name, string $plainPassword): array
    {
        if ($this->users->findByEmail($email) !== null) {
            throw new RuntimeException('Používateľ s týmto emailom už existuje');
        }

        $code = $this->generateCode();
        $challengeId = bin2hex(random_bytes(16));
        $ttlMinutes = $this->otpTtlMinutes();
        $expiresAt = time() + ($ttlMinutes * 60);

        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setPassword($plainPassword);
        $user->setRoles(['USER']);
        $user->setActive(false);

        $this->store->save([
            'id' => $challengeId,
            'flow' => self::FLOW_REGISTRATION,
            'email' => $email,
            'code_hash' => $this->hashCode($challengeId, $code),
            'payload' => [
                'name' => $name,
                'email' => $email,
                'password_hash' => $user->getPasswordHash(),
                'roles' => ['USER'],
            ],
            'expires_at' => $expiresAt,
            'attempts' => 0,
            'created_at' => time(),
        ]);

        $sent = $this->sendCodeEmail(
            $email,
            'PaginiumCMS — overovací kód registrácie',
            sprintf(
                "Váš overovací kód pre dokončenie registrácie: %s\n\nPlatnosť: %d minút.",
                $code,
                $ttlMinutes
            )
        );

        $result = [
            'challenge_id' => $challengeId,
            'expires_at' => $expiresAt,
        ];

        if (!$sent && $this->exposeDebugCode()) {
            $result['debug_code'] = $code;
        }

        return $result;
    }

    /**
     * @return array{user: array<string, mixed>}
     */
    public function verifyRegistration(string $challengeId, string $code): array
    {
        $challenge = $this->store->find($challengeId);
        if ($challenge === null || $challenge['flow'] !== self::FLOW_REGISTRATION) {
            throw new RuntimeException('Neplatná alebo expirovaná výzva');
        }

        $this->assertValidCode($challenge, $challengeId, $code);

        $payload = $challenge['payload'];
        $email = (string) ($payload['email'] ?? $challenge['email']);
        if ($email === '' || $this->users->findByEmail($email) !== null) {
            $this->store->delete($challengeId);
            throw new RuntimeException('Registráciu nie je možné dokončiť');
        }

        $user = new User();
        $user->setEmail($email);
        $user->setName((string) ($payload['name'] ?? ''));
        $user->setPasswordHash((string) ($payload['password_hash'] ?? ''));
        $user->setRoles(is_array($payload['roles'] ?? null) ? $payload['roles'] : ['USER']);
        $user->setActive(true);

        $this->users->save($user);
        $this->store->delete($challengeId);

        return ['user' => $user->jsonSerialize()];
    }

    /**
     * @return array{challenge_id: string, expires_at: int, debug_code?: string}
     */
    public function resendRegistration(string $challengeId): array
    {
        $challenge = $this->store->find($challengeId);
        if ($challenge === null || $challenge['flow'] !== self::FLOW_REGISTRATION) {
            throw new RuntimeException('Neplatná alebo expirovaná výzva');
        }

        $code = $this->generateCode();
        $ttlMinutes = $this->otpTtlMinutes();
        $expiresAt = time() + ($ttlMinutes * 60);

        $challenge['code_hash'] = $this->hashCode($challengeId, $code);
        $challenge['expires_at'] = $expiresAt;
        $challenge['attempts'] = 0;
        $this->store->save($challenge);

        $email = $challenge['email'];
        $sent = $this->sendCodeEmail(
            $email,
            'PaginiumCMS — nový overovací kód registrácie',
            sprintf("Váš nový overovací kód: %s\n\nPlatnosť: %d minút.", $code, $ttlMinutes)
        );

        $result = [
            'challenge_id' => $challengeId,
            'expires_at' => $expiresAt,
        ];

        if (!$sent && $this->exposeDebugCode()) {
            $result['debug_code'] = $code;
        }

        return $result;
    }

    /**
     * @param array{
     *     id: string,
     *     flow: string,
     *     email: string,
     *     code_hash: string,
     *     payload: array<string, mixed>,
     *     expires_at: int,
     *     attempts: int,
     *     created_at: int
     * } $challenge
     */
    private function assertValidCode(array $challenge, string $challengeId, string $code): void
    {
        $maxAttempts = max(1, (int) ($this->settings->group('workflows')['otpMaxAttempts'] ?? 5));
        $attempts = $challenge['attempts'];
        if ($attempts >= $maxAttempts) {
            $this->store->delete($challengeId);
            throw new RuntimeException('Prekročený počet pokusov — požiadajte o nový kód');
        }

        $expected = $challenge['code_hash'];
        $actual = $this->hashCode($challengeId, trim($code));
        if ($expected === '' || !hash_equals($expected, $actual)) {
            $challenge['attempts'] = $attempts + 1;
            $this->store->save($challenge);
            throw new RuntimeException('Neplatný overovací kód');
        }
    }

    private function hashCode(string $challengeId, string $code): string
    {
        return hash('sha256', $challengeId . '|' . $code);
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function otpTtlMinutes(): int
    {
        $workflows = $this->settings->group('workflows');

        return max(5, min(120, (int) ($workflows['otpTtlMinutes'] ?? 15)));
    }

    private function sendCodeEmail(string $to, string $subject, string $message): bool
    {
        if ($to === '') {
            return false;
        }

        if (!in_array('email', $this->notifications->getAdapters(), true)) {
            return false;
        }

        return $this->notifications->send('email', $to, $subject, $message, [
            'event' => 'otp_workflow',
            'severity' => 'info',
        ]);
    }

    private function exposeDebugCode(): bool
    {
        $env = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'production');

        return in_array($env, ['development', 'testing'], true);
    }
}
