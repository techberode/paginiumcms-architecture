<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Services;

use PaginiumCMS\Core\Drafts\Contracts\DraftManagerInterface;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Versioning\Services\EnhancedVersionManager;

/**
 * Applies configurable trash, content-version, and draft-orphan retention from settings.
 */
final class ContentStorageRetentionService
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private TrashService $trash,
        private EnhancedVersionManager $versions,
        private DraftManagerInterface $drafts
    ) {
    }

    /**
     * @return array{
     *     trash_removed: int,
     *     versions_removed: int,
     *     draft_orphans_removed: int,
     *     trash_enabled: bool,
     *     versions_enabled: bool,
     *     draft_prune_enabled: bool
     * }
     */
    public function run(): array
    {
        $cfg = $this->settings->group('storageRetention');

        $trashEnabled = (bool) ($cfg['trashAutoPurgeEnabled'] ?? false);
        $versionsEnabled = (bool) ($cfg['contentVersionRetentionEnabled'] ?? false);
        $draftEnabled = (bool) ($cfg['draftOrphanPruneEnabled'] ?? true);

        $trashRemoved = 0;
        if ($trashEnabled) {
            $days = max(1, (int) ($cfg['trashRetentionDays'] ?? 1));
            $trashRemoved = $this->trash->purgeOlderThan($days);
        }

        $versionsRemoved = 0;
        if ($versionsEnabled) {
            $days = max(1, (int) ($cfg['contentVersionRetentionDays'] ?? 30));
            $versionsRemoved = $this->versions->purgeVersionsOlderThan($days, 1);
        }

        $draftRemoved = 0;
        if ($draftEnabled) {
            $draftRemoved = $this->drafts->pruneUnsavedNewOrphansKeepLatest();
        }

        return [
            'trash_removed' => $trashRemoved,
            'versions_removed' => $versionsRemoved,
            'draft_orphans_removed' => $draftRemoved,
            'trash_enabled' => $trashEnabled,
            'versions_enabled' => $versionsEnabled,
            'draft_prune_enabled' => $draftEnabled,
        ];
    }
}
