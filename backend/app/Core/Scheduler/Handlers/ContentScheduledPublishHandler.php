<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler\Handlers;

use PaginiumCMS\Core\Content\Services\ContentPublishNotificationService;
use PaginiumCMS\Core\FlatFile\Services\ContentScheduledPublishService;
use PaginiumCMS\Core\Scheduler\Contracts\JobHandlerInterface;
use PaginiumCMS\Core\Scheduler\Models\JobRunResult;

final class ContentScheduledPublishHandler implements JobHandlerInterface
{
    public function __construct(
        private ContentScheduledPublishService $scheduledPublish,
        private ContentPublishNotificationService $publishNotifications,
    ) {
    }

    public function key(): string
    {
        return 'content.scheduled_publish';
    }

    public function label(): string
    {
        return 'Scheduled content publish';
    }

    public function handle(array $payload = []): JobRunResult
    {
        $result = $this->scheduledPublish->publishDueItems();
        $this->publishNotifications->notifySkippedItems($result['skipped']);

        $publishedCount = count($result['published']);
        $skippedCount = count($result['skipped']);

        $diagnostics = $result['diagnostics'];
        $inspected = $diagnostics['inspected'];

        $waitingCount = 0;
        $actionableBlocked = 0;
        foreach ($inspected as $row) {
            $reason = $row['blocking_reason'];
            if ($reason === 'not_due') {
                ++$waitingCount;
            } elseif (in_array($reason, ['missing_scheduled_at', 'otp_not_approved', 'save_failed'], true)) {
                ++$actionableBlocked;
            }
        }

        if ($publishedCount > 0) {
            $message = sprintf(
                'Published %d scheduled item(s): %s',
                $publishedCount,
                $this->formatPublishedSummary($result['published'])
            );
            $reason = null;
            $success = true;
        } elseif ($skippedCount > 0 || $actionableBlocked > 0) {
            $message = 'Scheduled items skipped';
            $reason = 'some_items_skipped';
            $success = false;
        } elseif ($waitingCount > 0) {
            $message = sprintf('%d item(s) waiting for scheduled time', $waitingCount);
            $reason = 'not_due';
            $success = false;
        } elseif ($diagnostics['queue_count'] > 0) {
            $message = 'Scheduled content in queue but nothing due yet';
            $reason = 'not_due';
            $success = false;
        } else {
            $message = 'No scheduled content due';
            $reason = 'nothing_due';
            $success = false;
        }

        return new JobRunResult($success, $message, $result, $reason);
    }

    /**
     * @param list<array{type?: string, slug?: string}> $published
     */
    private function formatPublishedSummary(array $published): string
    {
        $parts = [];
        foreach ($published as $row) {
            $type = trim((string) ($row['type'] ?? 'content'));
            $slug = trim((string) ($row['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }
            $parts[] = $type . '/' . $slug;
        }

        return $parts !== [] ? implode(', ', $parts) : '—';
    }
}
