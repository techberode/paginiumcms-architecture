<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Gdpr\Services;

use PaginiumCMS\Core\Gdpr\GdprPseudonym;
use PaginiumCMS\Modules\Comments\Services\CommentsRepository;
use PaginiumCMS\Modules\Messages\Services\MessageRepository;
use PaginiumCMS\Modules\Newsletter\Contracts\NewsletterRepositoryInterface;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\UserAvatarService;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use RuntimeException;

/**
 * Irreversibly redacts PII for a CMS user across primary flat-file stores (It.80e).
 */
final class GdprAnonymizeService
{
    public function __construct(
        private UserRepository $users,
        private UserAvatarService $avatars,
        private CommentsRepository $comments,
        private MessageRepository $messages,
        private NewsletterRepositoryInterface $newsletter,
    ) {
    }

    /**
     * @return array{
     *     userId: string,
     *     pseudonym: string,
     *     commentsUpdated: int,
     *     contactMessagesUpdated: int,
     *     newsletterUpdated: bool
     * }
     */
    public function anonymize(User $user): array
    {
        if (GdprPseudonym::isAnonymizedEmail($user->getEmail())) {
            throw new RuntimeException('User account is already anonymized.');
        }

        $pseudonym = GdprPseudonym::forSubject($user->getId());
        $pseudonymEmail = GdprPseudonym::emailForSubject($user->getId());
        $originalEmail = strtolower(trim($user->getEmail()));
        $originalName = trim($user->getName());

        $commentsUpdated = 0;
        foreach ($this->comments->findAll([
            'email' => $originalEmail,
            'authorName' => $originalName !== '' ? $originalName : null,
        ]) as $comment) {
            $comment->setAuthor($pseudonym);
            $comment->setEmail($pseudonymEmail);
            $this->comments->update($comment);
            ++$commentsUpdated;
        }

        $messagesUpdated = 0;
        foreach ($this->messages->findByEmail($originalEmail) as $message) {
            $message->setName($pseudonym);
            $message->setEmail($pseudonymEmail);
            $message->setIp('redacted');
            $this->messages->update($message);
            ++$messagesUpdated;
        }

        $newsletterUpdated = $this->newsletter->anonymizeEmail($originalEmail, $pseudonymEmail);

        $this->avatars->remove($user);
        $user->setEmail($pseudonymEmail);
        $user->setName($pseudonym);
        $user->setUsername($this->resolveUniqueUsername($pseudonym, $user->getId()));
        $user->setAvatarUrl(null);
        $user->setActive(false);
        $user->setTwoFactorEnabled(false);
        $user->setTwoFactorSecret(null);
        $user->setTwoFactorVerifiedAt(null);
        $user->setRoles([AuthorizationInterface::ROLE_USER]);
        $user->setPasswordHash(password_hash(bin2hex(random_bytes(32)), PASSWORD_ARGON2ID));
        $user->setUpdatedAt(time());
        $this->users->save($user);

        return [
            'userId' => $user->getId(),
            'pseudonym' => $pseudonym,
            'commentsUpdated' => $commentsUpdated,
            'contactMessagesUpdated' => $messagesUpdated,
            'newsletterUpdated' => $newsletterUpdated,
        ];
    }

    private function resolveUniqueUsername(string $base, string $userId): string
    {
        $candidate = strtolower(preg_replace('/[^a-z0-9_-]/', '', $base) ?: 'anon');
        if (!$this->users->existsByUsername($candidate, $userId)) {
            return $candidate;
        }

        $suffix = substr(hash('sha256', $userId), 0, 6);

        return substr($candidate, 0, max(2, 64 - strlen($suffix) - 1)) . '_' . $suffix;
    }
}
