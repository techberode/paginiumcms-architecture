<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler\Handlers;

use PaginiumCMS\Core\FlatFile\Services\ContentScheduledPublishService;
use PaginiumCMS\Core\Scheduler\Contracts\JobHandlerInterface;
use PaginiumCMS\Core\Scheduler\Models\JobRunResult;

final class ContentScheduledPublishHandler implements JobHandlerInterface
{
    public function __construct(private ContentScheduledPublishService $scheduledPublish)
    {
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
        $publishedCount = count($result['published']);
        $skippedCount = count($result['skipped']);

        $message = $publishedCount > 0
            ? sprintf('Published %d scheduled item(s)', $publishedCount)
            : ($skippedCount > 0 ? 'Scheduled items skipped' : 'No scheduled content due');

        return new JobRunResult(
            $publishedCount > 0,
            $message,
            $result,
            $skippedCount > 0 ? 'some_items_skipped' : null
        );
    }
}
