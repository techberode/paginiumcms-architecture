<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Messages\Services;

use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Teams\Services\TeamRepository;
use PaginiumCMS\Modules\Messages\Contracts\MessageRepositoryInterface;
use PaginiumCMS\Modules\Messages\Models\ContactMessage;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PaginiumCMS\Support\LogSanitizer;

/**
 * Routes inbound Messages, claims a flock lock, and continues Messenger threads (It.93o-4).
 */
final class MessageDeskService
{
    public function __construct(
        private MessageRepositoryInterface $messages,
        private MessageRoutingStore $routing,
        private TeamRepository $teams,
        private UserRepository $users,
        private ?NotificationService $notifications = null,
        private ?VisitorReplyMailer $visitorMail = null,
    ) {
    }

    /**
     * @return array{message: ContactMessage, appended: bool}
     */
    public function ingest(ContactMessage $incoming): array
    {
        $open = $this->findOpenThread($incoming);
        if ($open !== null) {
            $open->addReply([
                'authorType' => 'visitor',
                'authorName' => $incoming->getName(),
                'body' => $incoming->getMessage(),
            ]);
            $open->markRead(false);
            $this->messages->update($open);
            $this->notify($open, $this->notifyTargets($open), 'reply');

            return ['message' => $open, 'appended' => true];
        }

        $this->applyRoute($incoming);
        $this->messages->save($incoming);
        $this->notify($incoming, $this->notifyTargets($incoming), 'new');

        return ['message' => $incoming, 'appended' => false];
    }

    public function applyRoute(ContactMessage $message): void
    {
        if ($message->getStaffUserId() !== '') {
            $message->setAssigneeUserIds([$message->getStaffUserId()]);
        }

        $route = $this->routing->match($message->getSubject());
        if ($route === null) {
            return;
        }

        $users = $message->getAssigneeUserIds();
        foreach ($route['userIds'] as $userId) {
            if (!in_array($userId, $users, true)) {
                $users[] = $userId;
            }
        }
        $message->setAssigneeUserIds($users);
        $message->setAssigneeTeamIds($route['teamIds']);
    }

    public function claim(ContactMessage $message, User $actor): bool
    {
        if ($message->getClaimedBy() !== '' && $message->getClaimedBy() !== $actor->getId() && !$actor->isAdmin()) {
            return false;
        }

        $message->setClaimedBy($actor->getId());
        $message->setClaimedAt(time());
        $message->setHandleStatus(ContactMessage::STATUS_IN_PROGRESS);
        $message->setNotifyUserId($actor->getId());
        $message->markRead(true);
        $this->messages->update($message);

        return true;
    }

    public function release(ContactMessage $message, User $actor): bool
    {
        if ($message->getClaimedBy() === '') {
            return true;
        }
        if ($message->getClaimedBy() !== $actor->getId() && !$actor->isAdmin()) {
            return false;
        }

        $message->setClaimedBy('');
        $message->setClaimedAt(0);
        $message->setNotifyUserId('');
        if (!$message->isProcessed()) {
            $message->setHandleStatus(ContactMessage::STATUS_OPEN);
        }
        $this->messages->update($message);

        return true;
    }

    public function reply(ContactMessage $message, User $actor, string $body): bool
    {
        $body = trim($body);
        if ($body === '' || !$this->canReply($message, $actor)) {
            return false;
        }

        if ($message->getClaimedBy() === '') {
            $this->claim($message, $actor);
        } elseif ($message->getClaimedBy() !== $actor->getId() && !$actor->isAdmin()) {
            return false;
        }

        $message->addReply([
            'authorType' => 'staff',
            'authorUserId' => $actor->getId(),
            'authorName' => $actor->getName() !== '' ? $actor->getName() : $actor->getEmail(),
            'body' => $body,
        ]);
        $message->markRead(true);
        $this->messages->update($message);
        $this->visitorMail?->send(
            $message->getEmail(),
            $actor,
            'Re: ' . $message->getSubject(),
            $body,
            $message
        );

        return true;
    }

    public function canSee(ContactMessage $message, User $actor): bool
    {
        if ($actor->isAdmin()) {
            return true;
        }

        return in_array($actor->getId(), $this->visibleUserIds($message), true);
    }

    public function canReply(ContactMessage $message, User $actor): bool
    {
        if ($message->isArchived() || $message->isProcessed()) {
            return false;
        }

        return $this->canSee($message, $actor);
    }

    /**
     * @return list<ContactMessage>
     */
    public function visibleFor(User $actor): array
    {
        $items = [];
        foreach ($this->messages->findAll() as $message) {
            if ($this->canSee($message, $actor)) {
                $items[] = $message;
            }
        }

        usort($items, function (ContactMessage $a, ContactMessage $b) use ($actor): int {
            $desk = $this->deskRank($b, $actor) <=> $this->deskRank($a, $actor);
            if ($desk !== 0) {
                return $desk;
            }

            return strcmp($b->getCreatedAt(), $a->getCreatedAt());
        });

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    public function present(ContactMessage $message, User $actor): array
    {
        $payload = $message->jsonSerialize();
        $mine = $this->isMine($message, $actor);
        $payload['desk'] = $mine ? 'priority' : 'later';
        $payload['mine'] = $mine;
        $payload['canClaim'] = $this->canReply($message, $actor)
            && ($message->getClaimedBy() === '' || $message->getClaimedBy() === $actor->getId() || $actor->isAdmin());
        $payload['canReply'] = $this->canReply($message, $actor)
            && ($message->getClaimedBy() === '' || $message->getClaimedBy() === $actor->getId() || $actor->isAdmin());
        $payload['claimedByName'] = $this->displayName($message->getClaimedBy());

        return $payload;
    }

    public function isMine(ContactMessage $message, User $actor): bool
    {
        if ($message->isProcessed() || $message->isArchived()) {
            return false;
        }

        if ($message->getClaimedBy() === $actor->getId() || $message->getStaffUserId() === $actor->getId()) {
            return true;
        }

        return in_array($actor->getId(), $this->visibleUserIds($message), true);
    }

    /**
     * @return array{schema: string, enabled: bool, routes: list<array{subject: string, enabled: bool, teamIds: list<string>, userIds: list<string>}>}
     */
    public function routing(): array
    {
        return $this->routing->get();
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{schema: string, enabled: bool, routes: list<array{subject: string, enabled: bool, teamIds: list<string>, userIds: list<string>}>}
     */
    public function saveRouting(array $payload): array
    {
        return $this->routing->save($payload);
    }

    private function findOpenThread(ContactMessage $incoming): ?ContactMessage
    {
        foreach ($this->messages->findByEmail($incoming->getEmail()) as $existing) {
            if ($existing->isArchived() || $existing->isProcessed()) {
                continue;
            }
            if ($incoming->getStaffUserId() !== '' && $existing->getStaffUserId() === $incoming->getStaffUserId()) {
                return $existing;
            }
            if (
                $incoming->getStaffUserId() === ''
                && $existing->getStaffUserId() === ''
                && MessageRoutingStore::normalizeSubject($existing->getSubject())
                    === MessageRoutingStore::normalizeSubject($incoming->getSubject())
            ) {
                return $existing;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function visibleUserIds(ContactMessage $message): array
    {
        $ids = $message->getAssigneeUserIds();
        if ($message->getStaffUserId() !== '' && !in_array($message->getStaffUserId(), $ids, true)) {
            $ids[] = $message->getStaffUserId();
        }
        if ($message->getClaimedBy() !== '' && !in_array($message->getClaimedBy(), $ids, true)) {
            $ids[] = $message->getClaimedBy();
        }
        foreach ($message->getAssigneeTeamIds() as $teamId) {
            $team = $this->teams->get($teamId);
            if ($team === null) {
                continue;
            }
            $members = is_array($team['memberUserIds'] ?? null) ? $team['memberUserIds'] : [];
            foreach ($members as $memberId) {
                if (is_string($memberId) && $memberId !== '' && !in_array($memberId, $ids, true)) {
                    $ids[] = $memberId;
                }
            }
        }

        return $ids;
    }

    /**
     * @return list<string>
     */
    private function notifyTargets(ContactMessage $message): array
    {
        if ($message->getNotifyUserId() !== '') {
            return [$message->getNotifyUserId()];
        }
        if ($message->getClaimedBy() !== '') {
            return [$message->getClaimedBy()];
        }

        return $this->visibleUserIds($message);
    }

    private function deskRank(ContactMessage $message, User $actor): int
    {
        if ($this->isMine($message, $actor)) {
            return $message->getClaimedBy() === $actor->getId() ? 3 : 2;
        }

        return $message->isProcessed() || $message->isArchived() ? 0 : 1;
    }

    private function displayName(string $userId): string
    {
        if ($userId === '') {
            return '';
        }
        $user = $this->users->findById($userId);
        if ($user === null) {
            return '';
        }

        return $user->getName() !== '' ? $user->getName() : $user->getEmail();
    }

    /**
     * @param list<string> $userIds
     */
    private function notify(ContactMessage $message, array $userIds, string $kind): void
    {
        if ($this->notifications === null || $userIds === []) {
            return;
        }

        $subject = $kind === 'reply'
            ? 'New reply on a staff conversation'
            : 'New message on your desk';
        $preview = LogSanitizer::value($message->getSubject(), 120);

        foreach ($userIds as $userId) {
            $user = $this->users->findById($userId);
            if ($user === null || !$user->isActive() || $user->getEmail() === '') {
                continue;
            }
            try {
                $this->notifications->send(
                    'email',
                    $user->getEmail(),
                    $subject,
                    'Subject: ' . $preview
                );
            } catch (\Throwable) {
                // Email channel is optional; desk still works without SMTP.
            }
        }
    }
}
