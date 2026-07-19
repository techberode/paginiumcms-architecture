<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Workflow\Services;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Versioning\Services\ContentVersioningService;
use PaginiumCMS\Modules\Comments\Contracts\CommentsRepositoryInterface;
use PaginiumCMS\Modules\Comments\Models\Comment;
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
        private UserRepository $users,
        private CommentsRepositoryInterface $comments,
        private ContentRepositoryInterface $content,
        private ContentVersioningService $versioning
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
     * @return array{challenge_id: string, expires_at: int, debug_code?: string}
     */
    public function startCommentApproval(User $editor, string $commentId): array
    {
        if (!$this->isCommentApprovalOtpEnabled()) {
            throw new RuntimeException('OTP schválenie komentára nie je zapnuté');
        }

        $comment = $this->comments->findById($commentId);
        if ($comment === null) {
            throw new RuntimeException('Komentár neexistuje');
        }

        if ($comment->getStatus() === Comment::STATUS_APPROVED) {
            throw new RuntimeException('Komentár je už schválený');
        }

        return $this->startEditorChallenge(
            self::FLOW_COMMENT_APPROVAL,
            $editor,
            [
                'comment_id' => $commentId,
                'editor_id' => $editor->getId(),
            ],
            'PaginiumCMS — overovací kód schválenia komentára',
            'Váš overovací kód pre schválenie komentára: %s\n\nPlatnosť: %d minút.'
        );
    }

    /**
     * @return array{comment: array<string, mixed>}
     */
    public function verifyCommentApproval(string $challengeId, string $code, User $editor): array
    {
        $payload = $this->verifyEditorChallenge($challengeId, $code, $editor, self::FLOW_COMMENT_APPROVAL);
        $commentId = (string) ($payload['comment_id'] ?? '');
        $comment = $this->comments->findById($commentId);

        if ($comment === null) {
            $this->store->delete($challengeId);
            throw new RuntimeException('Komentár neexistuje');
        }

        $comment->setStatus(Comment::STATUS_APPROVED);

        try {
            $this->comments->update($comment);
        } catch (FlatFileException $e) {
            $this->store->delete($challengeId);
            throw new RuntimeException($e->getMessage(), 0, $e);
        }

        $this->store->delete($challengeId);

        /** @var array<string, mixed> $commentData */
        $commentData = $comment->jsonSerialize();

        return ['comment' => $commentData];
    }

    /**
     * @return array{challenge_id: string, expires_at: int, debug_code?: string}
     */
    public function startPublishApproval(User $editor, string $contentType, string $slug, string $targetStatus = 'published'): array
    {
        if (!$this->isPublishApprovalOtpEnabled()) {
            throw new RuntimeException('OTP publikácia nie je zapnutá');
        }

        if (!in_array($contentType, ['page', 'article'], true)) {
            throw new RuntimeException('Neplatný typ obsahu');
        }

        if (!in_array($targetStatus, ['published'], true)) {
            throw new RuntimeException('OTP je vyžadované len pre publikovanie');
        }

        $existing = $this->content->findBySlug($slug, $contentType);
        if ($existing === null) {
            throw new RuntimeException('Obsah neexistuje');
        }

        if ($existing->getStatus() === $targetStatus) {
            throw new RuntimeException('Obsah je už publikovaný');
        }

        return $this->startEditorChallenge(
            self::FLOW_PUBLISH_APPROVAL,
            $editor,
            [
                'content_type' => $contentType,
                'slug' => $slug,
                'target_status' => $targetStatus,
                'editor_id' => $editor->getId(),
            ],
            'PaginiumCMS — overovací kód publikácie',
            'Váš overovací kód pre publikovanie obsahu: %s\n\nPlatnosť: %d minút.'
        );
    }

    /**
     * @return array{
     *     content_type: string,
     *     slug: string,
     *     status: string,
     *     title: string
     * }
     */
    public function verifyPublishApproval(string $challengeId, string $code, User $editor): array
    {
        $payload = $this->verifyEditorChallenge($challengeId, $code, $editor, self::FLOW_PUBLISH_APPROVAL);
        $contentType = (string) ($payload['content_type'] ?? '');
        $slug = (string) ($payload['slug'] ?? '');
        $targetStatus = (string) ($payload['target_status'] ?? 'published');

        $existing = $this->content->findBySlug($slug, $contentType);
        if ($existing === null) {
            $this->store->delete($challengeId);
            throw new RuntimeException('Obsah neexistuje');
        }

        try {
            $existing->setStatus($targetStatus);
            $this->content->save($existing);
            $this->versioning->recordChange($existing, $contentType, 'status', $editor);
        } catch (FlatFileException $e) {
            $this->store->delete($challengeId);
            throw new RuntimeException($e->getMessage(), 0, $e);
        }

        $this->store->delete($challengeId);

        return [
            'content_type' => $contentType,
            'slug' => $slug,
            'status' => $targetStatus,
            'title' => $existing->getTitle(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function verifyAdminOtp(string $challengeId, string $code, User $editor): array
    {
        $challenge = $this->store->find($challengeId);
        if ($challenge === null) {
            throw new RuntimeException('Neplatná alebo expirovaná výzva');
        }

        return match ($challenge['flow']) {
            self::FLOW_COMMENT_APPROVAL => $this->verifyCommentApproval($challengeId, $code, $editor),
            self::FLOW_PUBLISH_APPROVAL => $this->verifyPublishApproval($challengeId, $code, $editor),
            default => throw new RuntimeException('Nepodporovaný typ OTP výzvy'),
        };
    }

    /**
     * @return array{challenge_id: string, expires_at: int, debug_code?: string}
     */
    public function resendAdminOtp(string $challengeId, User $editor): array
    {
        $challenge = $this->store->find($challengeId);
        if ($challenge === null) {
            throw new RuntimeException('Neplatná alebo expirovaná výzva');
        }

        if ($challenge['email'] !== $editor->getEmail()) {
            throw new RuntimeException('Výzva patrí inému používateľovi');
        }

        $payload = $challenge['payload'];
        if ((string) ($payload['editor_id'] ?? '') !== $editor->getId()) {
            throw new RuntimeException('Výzva patrí inému používateľovi');
        }

        return match ($challenge['flow']) {
            self::FLOW_COMMENT_APPROVAL => $this->resendEditorChallenge(
                $challenge,
                'PaginiumCMS — nový overovací kód schválenia komentára',
                'Váš nový overovací kód pre schválenie komentára: %s\n\nPlatnosť: %d minút.'
            ),
            self::FLOW_PUBLISH_APPROVAL => $this->resendEditorChallenge(
                $challenge,
                'PaginiumCMS — nový overovací kód publikácie',
                'Váš nový overovací kód pre publikovanie: %s\n\nPlatnosť: %d minút.'
            ),
            default => throw new RuntimeException('Nepodporovaný typ OTP výzvy'),
        };
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{challenge_id: string, expires_at: int, debug_code?: string}
     */
    private function startEditorChallenge(
        string $flow,
        User $editor,
        array $payload,
        string $subject,
        string $messageTemplate
    ): array {
        $email = $editor->getEmail();
        if ($email === '') {
            throw new RuntimeException('Editor nemá nastavený email');
        }

        $code = $this->generateCode();
        $challengeId = bin2hex(random_bytes(16));
        $ttlMinutes = $this->otpTtlMinutes();
        $expiresAt = time() + ($ttlMinutes * 60);

        $this->store->save([
            'id' => $challengeId,
            'flow' => $flow,
            'email' => $email,
            'code_hash' => $this->hashCode($challengeId, $code),
            'payload' => $payload,
            'expires_at' => $expiresAt,
            'attempts' => 0,
            'created_at' => time(),
        ]);

        $sent = $this->sendCodeEmail(
            $email,
            $subject,
            sprintf($messageTemplate, $code, $ttlMinutes)
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
     * @return array{challenge_id: string, expires_at: int, debug_code?: string}
     */
    private function resendEditorChallenge(array $challenge, string $subject, string $messageTemplate): array
    {
        $challengeId = $challenge['id'];
        $code = $this->generateCode();
        $ttlMinutes = $this->otpTtlMinutes();
        $expiresAt = time() + ($ttlMinutes * 60);

        $challenge['code_hash'] = $this->hashCode($challengeId, $code);
        $challenge['expires_at'] = $expiresAt;
        $challenge['attempts'] = 0;
        $this->store->save($challenge);

        $sent = $this->sendCodeEmail(
            $challenge['email'],
            $subject,
            sprintf($messageTemplate, $code, $ttlMinutes)
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
     * @return array<string, mixed>
     */
    private function verifyEditorChallenge(
        string $challengeId,
        string $code,
        User $editor,
        string $expectedFlow
    ): array {
        $challenge = $this->store->find($challengeId);
        if ($challenge === null || $challenge['flow'] !== $expectedFlow) {
            throw new RuntimeException('Neplatná alebo expirovaná výzva');
        }

        if ($challenge['email'] !== $editor->getEmail()) {
            throw new RuntimeException('Výzva patrí inému používateľovi');
        }

        $payload = $challenge['payload'];
        if ((string) ($payload['editor_id'] ?? '') !== $editor->getId()) {
            throw new RuntimeException('Výzva patrí inému používateľovi');
        }

        $this->assertValidCode($challenge, $challengeId, $code);

        return $payload;
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
