<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Newsletter\Handlers;

use PaginiumCMS\Core\Scheduler\Contracts\JobHandlerInterface;
use PaginiumCMS\Core\Scheduler\Models\JobRunResult;
use PaginiumCMS\Modules\Newsletter\Services\NewsletterMailService;

final class NewsletterWeeklyDigestHandler implements JobHandlerInterface
{
    public function __construct(private NewsletterMailService $mailService)
    {
    }

    public function key(): string
    {
        return 'newsletter.weekly_digest';
    }

    public function label(): string
    {
        return 'Newsletter weekly digest';
    }

    public function handle(array $payload = []): JobRunResult
    {
        $result = $this->mailService->sendWeeklyDigest();

        $message = match ($result['reason'] ?? null) {
            'send_disabled', 'weekly_digest_disabled' => 'Newsletter sending disabled in settings',
            'email_not_configured' => 'Email channel not configured',
            'no_articles' => 'No new articles for weekly digest',
            'no_subscribers' => 'No weekly digest subscribers',
            default => sprintf(
                'Weekly digest: sent=%d failed=%d skipped=%d',
                $result['sent'],
                $result['failed'],
                $result['skipped']
            ),
        };

        $success = $result['sent'] > 0
            || in_array($result['reason'] ?? '', ['no_articles', 'no_subscribers'], true);

        return new JobRunResult(
            $success,
            $message,
            $result,
            $result['sent'] > 0 ? null : ($result['reason'] ?? 'nothing_sent')
        );
    }
}
