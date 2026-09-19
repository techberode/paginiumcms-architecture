<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Messages\Services;

use PaginiumCMS\Core\Teams\Services\TeamRepository;
use PaginiumCMS\Modules\Comments\Contracts\CommentsRepositoryInterface;
use PaginiumCMS\Modules\Comments\Models\Comment;
use PaginiumCMS\Modules\Messages\Models\ContactMessage;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Support\LogSanitizer;

/**
 * Unified desk queue: routed messages + article comments (It.93o-5).
 */
final class DeskInboxService
{
    public function __construct(
        private MessageDeskService $messages,
        private CommentsRepositoryInterface $comments,
        private TeamRepository $teams,
        private ?VisitorReplyMailer $visitorMail = null,
    ) {
    }

    /**
     * @return array{
     *   hasDesk: bool,
     *   canReplyComments: bool,
     *   deskCount: int
     * }
     */
    public function status(User $actor): array
    {
        $canComments = $this->canReplyComments($actor);
        $count = count($this->items($actor));

        return [
            'hasDesk' => $canComments || $count > 0 || $this->inSupportOrEditorial($actor),
            'canReplyComments' => $canComments,
            'deskCount' => $count,
        ];
    }

    public function canReplyComments(User $actor): bool
    {
        return $actor->isEditor() || $this->inSupportOrEditorial($actor);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function items(User $actor): array
    {
        $items = [];
        try {
            foreach ($this->messages->visibleFor($actor) as $message) {
                if ($message->isProcessed() || $message->isArchived()) {
                    continue;
                }
                $mine = $this->messages->isMine($message, $actor);
                if (!$mine && $message->getClaimedBy() !== '' && $message->getClaimedBy() !== $actor->getId()) {
                    continue;
                }
                $items[] = $this->presentMessage($message, $actor, $mine);
            }
        } catch (\Throwable $exception) {
            error_log('desk_messages_failed ' . LogSanitizer::value($exception->getMessage(), 240));
        }

        if ($this->canReplyComments($actor)) {
            try {
                foreach ($this->openComments() as $comment) {
                    if ($comment->getClaimedBy() !== '' && $comment->getClaimedBy() !== $actor->getId() && !$actor->isAdmin()) {
                        continue;
                    }
                    $items[] = $this->presentComment($comment, $actor);
                }
            } catch (\Throwable $exception) {
                error_log('desk_comments_failed ' . LogSanitizer::value($exception->getMessage(), 240));
            }
        }

        usort($items, static function (array $a, array $b): int {
            $rank = ((int) ($b['priorityRank'] ?? 0)) <=> ((int) ($a['priorityRank'] ?? 0));
            if ($rank !== 0) {
                return $rank;
            }

            return strcmp((string) ($b['createdAt'] ?? ''), (string) ($a['createdAt'] ?? ''));
        });

        return $items;
    }

    public function claimComment(Comment $comment, User $actor): bool
    {
        if ($comment->getParentId() !== '') {
            return false;
        }
        if ($comment->getClaimedBy() !== '' && $comment->getClaimedBy() !== $actor->getId() && !$actor->isAdmin()) {
            return false;
        }

        $comment->setClaimedBy($actor->getId());
        $comment->setClaimedAt(time());
        $comment->setHandleStatus('in_progress');
        $this->comments->update($comment);

        return true;
    }

    public function replyToComment(Comment $parent, User $actor, string $body): ?Comment
    {
        $body = trim($body);
        if ($body === '' || $parent->getParentId() !== '' || !$this->canReplyComments($actor)) {
            return null;
        }
        if ($parent->getClaimedBy() !== '' && $parent->getClaimedBy() !== $actor->getId() && !$actor->isAdmin()) {
            return null;
        }

        if ($parent->getClaimedBy() === '') {
            $this->claimComment($parent, $actor);
        }

        $reply = new Comment($parent->getArticleSlug(), $actor->getName() !== '' ? $actor->getName() : $actor->getEmail(), $body);
        $reply->setEmail($actor->getEmail());
        $reply->setParentId($parent->getId());
        $reply->setAuthorUserId($actor->getId());
        $reply->setStatus(Comment::STATUS_APPROVED);
        $this->comments->save($reply);
        $this->visitorMail?->send(
            $parent->getEmail(),
            $actor,
            'Re: comment on ' . $parent->getArticleSlug(),
            $body
        );

        return $reply;
    }

    /**
     * @return list<Comment>
     */
    public function repliesFor(string $parentId): array
    {
        $out = [];
        foreach ($this->comments->findAll(['status' => Comment::STATUS_APPROVED]) as $comment) {
            if ($comment->getParentId() === $parentId) {
                $out[] = $comment;
            }
        }

        usort($out, static fn (Comment $a, Comment $b): int => strcmp($a->getCreatedAt(), $b->getCreatedAt()));

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public function publicComment(Comment $comment): array
    {
        $replies = [];
        if ($comment->getParentId() === '') {
            foreach ($this->repliesFor($comment->getId()) as $reply) {
                $replies[] = $this->publicComment($reply);
            }
        }

        return [
            'id' => $comment->getId(),
            'articleSlug' => $comment->getArticleSlug(),
            'author' => $comment->getAuthor(),
            'content' => $comment->getContent(),
            'status' => $comment->getStatus(),
            'createdAt' => $comment->getCreatedAt(),
            'approvedAt' => $comment->getApprovedAt(),
            'parentId' => $comment->getParentId(),
            'authorUserId' => $comment->getAuthorUserId(),
            'staffReply' => $comment->isStaffReply(),
            'handleStatus' => $comment->getHandleStatus(),
            'rating' => $comment->getRating(),
            'replies' => $replies,
        ];
    }

    private function inSupportOrEditorial(User $actor): bool
    {
        $id = $actor->getId();

        return in_array($id, $this->teams->memberIdsForType(TeamRepository::TYPE_SUPPORT), true)
            || in_array($id, $this->teams->memberIdsForType(TeamRepository::TYPE_EDITORIAL), true);
    }

    /**
     * @return list<Comment>
     */
    private function openComments(): array
    {
        $open = [];
        foreach ($this->comments->findAll(['status' => Comment::STATUS_APPROVED]) as $comment) {
            if ($comment->getParentId() !== '' || $comment->isArchived()) {
                continue;
            }
            if ($comment->getHandleStatus() === 'done') {
                continue;
            }
            $hasStaff = false;
            foreach ($this->repliesFor($comment->getId()) as $reply) {
                if ($reply->isStaffReply()) {
                    $hasStaff = true;
                    break;
                }
            }
            if ($hasStaff && $comment->getClaimedBy() === '') {
                continue;
            }
            $open[] = $comment;
        }

        return $open;
    }

    /**
     * @return array<string, mixed>
     */
    private function presentMessage(ContactMessage $message, User $actor, bool $mine): array
    {
        return [
            'kind' => 'message',
            'id' => $message->getId(),
            'title' => $message->getSubject(),
            'preview' => LogSanitizer::value($message->getMessage(), 160),
            'href' => '/messages#message-' . rawurlencode($message->getId()),
            'createdAt' => $message->getCreatedAt(),
            'handleStatus' => $message->getHandleStatus(),
            'claimedBy' => $message->getClaimedBy(),
            'mine' => $mine,
            'priority' => $message->getPriority(),
            'priorityRank' => $mine ? ($message->getClaimedBy() === $actor->getId() ? 3 : 2) : 1,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentComment(Comment $comment, User $actor): array
    {
        $mine = $comment->getClaimedBy() === $actor->getId();

        return [
            'kind' => 'comment',
            'id' => $comment->getId(),
            'title' => $comment->getAuthor(),
            'preview' => LogSanitizer::value($comment->getContent(), 160),
            'href' => '/comments#comment-' . rawurlencode($comment->getId()),
            'articleSlug' => $comment->getArticleSlug(),
            'createdAt' => $comment->getCreatedAt(),
            'handleStatus' => $comment->getHandleStatus(),
            'claimedBy' => $comment->getClaimedBy(),
            'mine' => $mine,
            'priorityRank' => $mine ? 3 : 2,
        ];
    }
}
