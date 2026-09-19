<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Messages\Services;

use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Modules\Messages\Models\ContactMessage;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Support\LogSanitizer;

final class VisitorReplyMailer
{
    public function __construct(
        private ReplyMailboxResolver $resolver,
        private ?NotificationService $notifications = null,
    ) {
    }

    public function send(string $toEmail, User $actor, string $subject, string $body, ?ContactMessage $thread = null): bool
    {
        if ($this->notifications === null) {
            return false;
        }
        $toEmail = strtolower(trim($toEmail));
        if ($toEmail === '' || filter_var($toEmail, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }
        $from = $this->resolver->resolve($actor, $thread);
        $options = [];
        if ($from !== null) {
            $options['reply_to'] = $from['email'];
        } elseif (filter_var($actor->getEmail(), FILTER_VALIDATE_EMAIL) !== false) {
            $options['reply_to'] = strtolower(trim($actor->getEmail()));
        }

        try {
            return $this->notifications->send(
                'email',
                $toEmail,
                LogSanitizer::value($subject, 160),
                $body,
                $options
            );
        } catch (\Throwable) {
            return false;
        }
    }
}
