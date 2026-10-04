<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler\Handlers;

use PaginiumCMS\Core\Content\Services\ContentNewsArchiveService;
use PaginiumCMS\Core\Scheduler\Contracts\JobHandlerInterface;
use PaginiumCMS\Core\Scheduler\Models\JobRunResult;

final class ContentNewsAutoArchiveHandler implements JobHandlerInterface
{
    public function __construct(private ContentNewsArchiveService $newsArchive)
    {
    }

    public function key(): string
    {
        return 'content.news_auto_archive';
    }

    public function label(): string
    {
        return 'News → archive (retention)';
    }

    public function handle(array $payload = []): JobRunResult
    {
        $result = $this->newsArchive->archiveDueNewsArticles();
        $count = count($result['archived']);

        if ($count > 0) {
            $slugs = implode(', ', array_map(static fn (array $row): string => $row['slug'], $result['archived']));

            return new JobRunResult(true, sprintf('Archived %d news article(s): %s', $count, $slugs), $result, null);
        }

        $skipped = count($result['skipped']);

        return new JobRunResult(
            false,
            $skipped > 0 ? sprintf('No news articles due (%d inspected)', $skipped) : 'No news articles due',
            $result,
            'nothing_due'
        );
    }
}
