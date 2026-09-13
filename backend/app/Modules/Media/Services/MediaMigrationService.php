<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Services;

use PaginiumCMS\Core\FlatFile\Models\MediaFile;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface;
use PaginiumCMS\Modules\Media\Contracts\MediaStorageDriverInterface;
use PaginiumCMS\Modules\Media\Exception\MediaMigrationException;

/**
 * Copy, verify, cutover, and rollback for local ↔ S3 media binary migration (Iteration 72c).
 *
 * Registry metadata remains flat-file SSOT; only binary location and public URL change on cutover.
 * Local originals are never deleted automatically.
 */
final class MediaMigrationService
{
    public function __construct(
        private MediaRepositoryInterface $mediaRepository,
        private MediaStorageFactory $storageFactory,
        private MediaStorageCapabilityProbe $capabilityProbe,
        private MediaMigrationJournalStore $journalStore,
        private SettingsRepositoryInterface $settings,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function inventory(string $sourceDriver = 'local', string $targetDriver = 's3'): array
    {
        $this->assertAllowedRoute($sourceDriver, $targetDriver);
        $mediaSettings = $this->settings->group('media');
        $source = $this->driver($sourceDriver, $mediaSettings);

        $items = [];
        $totalBytes = 0;
        $missingOnSource = 0;

        foreach ($this->mediaRepository->findAll() as $file) {
            $path = $file->getPath();
            $exists = $source->exists($path);
            if (!$exists) {
                ++$missingOnSource;
            }

            $sizeBytes = $file->getSizeBytes();
            $totalBytes += $sizeBytes;
            $items[] = [
                'path' => $path,
                'fileName' => $file->getFileName(),
                'sizeBytes' => $sizeBytes,
                'mimeType' => $file->getMimeType(),
                'existsOnSource' => $exists,
                'url' => $file->getUrl(),
            ];
        }

        return [
            'sourceDriver' => $sourceDriver,
            'targetDriver' => $targetDriver,
            'itemCount' => count($items),
            'totalBytes' => $totalBytes,
            'missingOnSource' => $missingOnSource,
            'items' => $items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dryRun(string $sourceDriver = 'local', string $targetDriver = 's3'): array
    {
        $inventory = $this->inventory($sourceDriver, $targetDriver);
        $mediaSettings = $this->settings->group('media');
        $target = $this->driver($targetDriver, $mediaSettings);
        $targetHealth = $target->health();
        $probe = $this->capabilityProbe->probe($target, $mediaSettings);

        return [
            'migrationId' => MediaMigrationJournalStore::proposeMigrationId(),
            'inventory' => $inventory,
            'targetHealth' => $targetHealth,
            'targetProbe' => [
                'status' => $probe['capabilities'][$targetDriver === 's3' ? 's3Storage' : 'localStorage']['status'] ?? 'unknown',
                'message' => $probe['capabilities'][$targetDriver === 's3' ? 's3Storage' : 'localStorage']['message'] ?? '',
            ],
            'canMigrate' => $inventory['missingOnSource'] === 0 && $targetHealth['ok'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function start(string $sourceDriver = 'local', string $targetDriver = 's3', ?string $migrationId = null): array
    {
        $dryRun = $this->dryRun($sourceDriver, $targetDriver);
        if (!$dryRun['canMigrate']) {
            throw new MediaMigrationException('Migration cannot start: missing source files or target storage unhealthy.');
        }

        $existing = $this->journalStore->load();
        if ($existing !== null && in_array($existing['status'] ?? '', ['copying', 'verified'], true)) {
            throw new MediaMigrationException('An active migration journal already exists. Resume copy or rollback first.');
        }

        $migrationId = $migrationId !== null && $migrationId !== ''
            ? MediaMigrationJournalStore::sanitizeMigrationId($migrationId)
            : (string) $dryRun['migrationId'];

        $items = [];
        foreach ($dryRun['inventory']['items'] as $item) {
            $path = (string) $item['path'];
            $source = $this->driver($sourceDriver, $this->settings->group('media'));
            $items[] = [
                'path' => $path,
                'sizeBytes' => (int) $item['sizeBytes'],
                'previousUrl' => (string) $item['url'],
                'sourceChecksum' => $source->checksum($path),
                'targetChecksum' => null,
                'status' => 'pending',
                'error' => null,
            ];
        }

        $journal = [
            'id' => $migrationId,
            'status' => 'copying',
            'sourceDriver' => $sourceDriver,
            'targetDriver' => $targetDriver,
            'startedAt' => time(),
            'updatedAt' => time(),
            'cutoverAt' => null,
            'items' => $items,
        ];

        $this->journalStore->save($journal);

        return [
            'migrationId' => $migrationId,
            'itemCount' => count($items),
            'status' => 'copying',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function copyBatch(string $migrationId, int $batchSize = 20, bool $dryRun = false): array
    {
        $journal = $this->journalStore->requireMatching($migrationId);
        if (($journal['status'] ?? '') !== 'copying') {
            throw new MediaMigrationException('Migration is not in copying state.');
        }

        $batchSize = max(1, min(200, $batchSize));
        $mediaSettings = $this->settings->group('media');
        $source = $this->driver((string) $journal['sourceDriver'], $mediaSettings);
        $target = $this->driver((string) $journal['targetDriver'], $mediaSettings);

        $processed = 0;
        $copied = 0;
        $skipped = 0;
        $failed = 0;

        /** @var list<array<string, mixed>> $items */
        $items = $journal['items'];
        foreach ($items as $index => $item) {
            if ($processed >= $batchSize) {
                break;
            }

            $status = (string) ($item['status'] ?? 'pending');
            if ($status !== 'pending') {
                ++$skipped;
                continue;
            }

            ++$processed;
            $path = (string) $item['path'];

            try {
                if ($dryRun) {
                    ++$copied;
                    continue;
                }

                if (!$source->exists($path)) {
                    throw new MediaMigrationException('Source object missing: ' . $path);
                }

                $binary = $source->read($path);
                $target->put($path, $binary);
                $items[$index]['targetChecksum'] = $target->checksum($path);
                $items[$index]['status'] = 'copied';
                $items[$index]['error'] = null;
                ++$copied;
            } catch (\Throwable $exception) {
                $items[$index]['status'] = 'failed';
                $items[$index]['error'] = $exception->getMessage();
                ++$failed;
            }
        }

        $journal['items'] = $items;
        $journal['updatedAt'] = time();
        $this->journalStore->save($journal);

        $pending = $this->countItemsByStatus($items, 'pending');

        return [
            'migrationId' => $migrationId,
            'dryRun' => $dryRun,
            'processed' => $processed,
            'copied' => $copied,
            'skipped' => $skipped,
            'failed' => $failed,
            'pending' => $pending,
            'complete' => $pending === 0 && $failed === 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function verify(string $migrationId): array
    {
        $journal = $this->journalStore->requireMatching($migrationId);
        if (!in_array($journal['status'] ?? '', ['copying', 'verified'], true)) {
            throw new MediaMigrationException('Migration cannot be verified in its current state.');
        }

        $mediaSettings = $this->settings->group('media');
        $source = $this->driver((string) $journal['sourceDriver'], $mediaSettings);
        $target = $this->driver((string) $journal['targetDriver'], $mediaSettings);

        $verified = 0;
        $failed = 0;

        /** @var list<array<string, mixed>> $items */
        $items = $journal['items'];
        foreach ($items as $index => $item) {
            $status = (string) ($item['status'] ?? 'pending');
            if (!in_array($status, ['copied', 'verified', 'failed'], true)) {
                continue;
            }

            $path = (string) $item['path'];
            try {
                $sourceChecksum = $source->checksum($path);
                $targetChecksum = $target->checksum($path);
                if (!hash_equals($sourceChecksum, $targetChecksum)) {
                    throw new MediaMigrationException('Checksum mismatch');
                }

                $items[$index]['sourceChecksum'] = $sourceChecksum;
                $items[$index]['targetChecksum'] = $targetChecksum;
                $items[$index]['status'] = 'verified';
                $items[$index]['error'] = null;
                ++$verified;
            } catch (\Throwable $exception) {
                $items[$index]['status'] = 'failed';
                $items[$index]['error'] = $exception->getMessage();
                ++$failed;
            }
        }

        $allVerified = $this->countItemsByStatus($items, 'verified') === count($items);
        $journal['items'] = $items;
        $journal['updatedAt'] = time();
        $journal['status'] = $allVerified && $failed === 0 ? 'verified' : 'copying';
        $this->journalStore->save($journal);

        return [
            'migrationId' => $migrationId,
            'verified' => $verified,
            'failed' => $failed,
            'allVerified' => $allVerified,
            'status' => $journal['status'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function cutover(string $migrationId, bool $confirmed = false): array
    {
        if (!$confirmed) {
            throw new MediaMigrationException('Cutover requires explicit confirmation.');
        }

        $journal = $this->journalStore->requireMatching($migrationId);
        if (($journal['status'] ?? '') !== 'verified') {
            throw new MediaMigrationException('Cutover requires a fully verified migration journal.');
        }

        $mediaSettings = $this->settings->group('media');
        $target = $this->driver((string) $journal['targetDriver'], $mediaSettings);
        $updated = 0;

        /** @var list<array<string, mixed>> $items */
        $items = $journal['items'];
        foreach ($items as $item) {
            $path = (string) $item['path'];
            $media = $this->mediaRepository->findByPath($path);
            if ($media === null) {
                throw new MediaMigrationException('Registry entry missing during cutover: ' . $path);
            }

            $media->setUrl($target->publicUrl($path));
            $this->mediaRepository->update($media);
            ++$updated;
        }

        $mediaSettings['storageDriver'] = (string) $journal['targetDriver'];
        $this->settings->setGroup('media', $mediaSettings);

        $journal['status'] = 'cutover';
        $journal['cutoverAt'] = time();
        $journal['updatedAt'] = time();
        $this->journalStore->save($journal);

        return [
            'migrationId' => $migrationId,
            'updatedUrls' => $updated,
            'activeDriver' => (string) $journal['targetDriver'],
            'status' => 'cutover',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rollback(string $migrationId, bool $confirmed = false): array
    {
        if (!$confirmed) {
            throw new MediaMigrationException('Rollback requires explicit confirmation.');
        }

        $journal = $this->journalStore->requireMatching($migrationId);
        $status = (string) ($journal['status'] ?? '');
        if (!in_array($status, ['copying', 'verified', 'cutover'], true)) {
            throw new MediaMigrationException('Migration cannot be rolled back in its current state.');
        }

        $mediaSettings = $this->settings->group('media');
        $target = $this->driver((string) $journal['targetDriver'], $mediaSettings);
        $restoredUrls = 0;
        $deletedTargets = 0;

        /** @var list<array<string, mixed>> $items */
        $items = $journal['items'];
        foreach ($items as $item) {
            $path = (string) $item['path'];
            $itemStatus = (string) ($item['status'] ?? 'pending');

            if ($status === 'cutover') {
                $media = $this->mediaRepository->findByPath($path);
                if ($media !== null) {
                    $media->setUrl((string) ($item['previousUrl'] ?? $media->getUrl()));
                    $this->mediaRepository->update($media);
                    ++$restoredUrls;
                }
            }

            if (in_array($itemStatus, ['copied', 'verified'], true) && $target->exists($path)) {
                $target->delete($path);
                ++$deletedTargets;
            }
        }

        if ($status === 'cutover') {
            $mediaSettings['storageDriver'] = (string) $journal['sourceDriver'];
            $this->settings->setGroup('media', $mediaSettings);
        }

        $journal['status'] = 'rolled_back';
        $journal['updatedAt'] = time();
        $this->journalStore->save($journal);

        return [
            'migrationId' => $migrationId,
            'restoredUrls' => $restoredUrls,
            'deletedTargets' => $deletedTargets,
            'activeDriver' => $status === 'cutover' ? (string) $journal['sourceDriver'] : $this->storageFactory->resolveActiveDriver($mediaSettings),
            'status' => 'rolled_back',
        ];
    }

    /**
     * @param array<string, mixed> $mediaSettings
     */
    private function driver(string $driver, array $mediaSettings): MediaStorageDriverInterface
    {
        return $this->storageFactory->create($driver, false, $mediaSettings);
    }

    private function assertAllowedRoute(string $sourceDriver, string $targetDriver): void
    {
        $allowed = [
            'local' => ['s3'],
            's3' => ['local'],
        ];

        if (!isset($allowed[$sourceDriver]) || !in_array($targetDriver, $allowed[$sourceDriver], true)) {
            throw new MediaMigrationException('Unsupported migration route.');
        }
    }

    /**
     * @param list<array<string, mixed>> $items
     */
    private function countItemsByStatus(array $items, string $status): int
    {
        $count = 0;
        foreach ($items as $item) {
            if (($item['status'] ?? '') === $status) {
                ++$count;
            }
        }

        return $count;
    }
}
