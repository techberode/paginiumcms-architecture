<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Git\Services;

use PaginiumCMS\Core\Git\Models\PublishQueueItem;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Builds commit plans from queued changes (Iteration 70).
 */
final class PublishPlanner
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
    ) {
    }

    /**
     * @param list<PublishQueueItem> $items
     * @return array{paths: list<string>, message: string, itemIds: list<string>}
     */
    public function planRelease(array $items): array
    {
        $byPath = [];
        foreach ($items as $item) {
            if ($item->status !== 'pending_publish') {
                continue;
            }
            $byPath[$item->resourcePath] = $item;
        }

        $selected = array_values($byPath);
        $paths = array_map(static fn (PublishQueueItem $item): string => $item->resourcePath, $selected);
        $ids = array_map(static fn (PublishQueueItem $item): string => $item->id, $selected);

        return [
            'paths' => $paths,
            'message' => $this->buildMessage(count($paths)),
            'itemIds' => $ids,
        ];
    }

    private function buildMessage(int $count): string
    {
        $engine = $this->settings->group('engine');
        $template = (string) ($engine['gitCommitMessageTemplate'] ?? 'content: publish {count} change(s)');

        return str_replace('{count}', (string) max(1, $count), $template);
    }

    public function messageForCount(int $count): string
    {
        return $this->buildMessage($count);
    }
}
