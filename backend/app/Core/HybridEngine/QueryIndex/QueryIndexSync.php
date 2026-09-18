<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\HybridEngine\QueryIndex;

use PaginiumCMS\Core\FlatFile\Models\ContentIndexEntry;
use PaginiumCMS\Core\Notification\Services\IncidentNotifier;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Dual-write hook: JSON index first, optional SQLite projection (It.92c).
 */
final class QueryIndexSync
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private QueryIndexCapabilityProbe $probe,
        private QueryIndexSqliteStore $store,
        private IncidentNotifier $incidents
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public function afterUpsert(array $row): void
    {
        if (!$this->shouldSync()) {
            return;
        }

        try {
            $this->store->upsertEntry(null, ContentIndexEntry::fromArray($row));
        } catch (\Throwable $e) {
            $this->recordIncident('upsert', $e);
        }
    }

    public function afterRemove(string $type, string $slug): void
    {
        if (!$this->shouldSync()) {
            return;
        }

        try {
            $this->store->deleteByTypeSlug($type, $slug);
        } catch (\Throwable $e) {
            $this->recordIncident('remove', $e);
        }
    }

    public function afterRemoveByPath(string $type, string $path): void
    {
        if (!$this->shouldSync()) {
            return;
        }

        try {
            $this->store->deleteByTypePath($type, $path);
        } catch (\Throwable $e) {
            $this->recordIncident('remove_path', $e);
        }
    }

    /**
     * @param list<array<string, mixed>> $items
     */
    public function afterFullJsonRebuild(array $items): void
    {
        if (!$this->shouldSync()) {
            return;
        }

        try {
            $this->store->replaceAllFromEntries($items);
        } catch (\Throwable $e) {
            $this->recordIncident('rebuild', $e);
        }
    }

    private function shouldSync(): bool
    {
        $driver = (string) ($this->settings->get('engine.queryIndexDriver') ?? QueryIndexInterface::DRIVER_JSON);

        return $driver === QueryIndexInterface::DRIVER_SQLITE && $this->probe->runtimeReady();
    }

    private function recordIncident(string $action, \Throwable $e): void
    {
        $this->incidents->notify(
            'query_index.sync',
            'Query index SQLite sync failed',
            sprintf('Action %s failed: %s', $action, $e->getMessage()),
            'warning'
        );
    }
}
