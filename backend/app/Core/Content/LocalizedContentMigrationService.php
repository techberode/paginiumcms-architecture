<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Content;

use PaginiumCMS\Core\Cache\ContentCacheService;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Models\Content;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Support\AppTimezone;
use PaginiumCMS\Support\JsonHelper;

/**
 * Inventory, dry-run, batch conversion, and rollback for schema v2 locale migration (Iteration 73 Phase 2d).
 */
final class LocalizedContentMigrationService
{
    private const MIGRATION_DIR = 'data/migrations';

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private ContentRepositoryInterface $repository,
        private ContentIndexService $index,
        private ContentCacheService $contentCache,
        private LocalizedContentNormalizer $normalizer,
        private LocalizedContentWriter $localizedWriter,
        private SettingsRepositoryInterface $settings,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function inventory(): array
    {
        $documents = $this->collectDocuments();
        $conflicts = $this->detectConflicts($documents);

        return [
            'defaultLocale' => $this->siteDefaultLocale(),
            'totals' => $this->summarizeDocuments($documents, $conflicts),
            'documents' => $documents,
            'conflicts' => $conflicts,
            'blockingConflicts' => $conflicts !== [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dryRun(string $defaultLocale): array
    {
        $defaultLocale = $this->assertLocale($defaultLocale);
        $inventory = $this->inventory();
        $plan = $this->buildConversionPlan($inventory['documents'], $inventory['conflicts'], $defaultLocale);

        return [
            'migrationId' => $this->proposeMigrationId(),
            'defaultLocale' => $defaultLocale,
            'blockingConflicts' => $inventory['blockingConflicts'],
            'conflicts' => $inventory['conflicts'],
            'wouldConvert' => count($plan['candidates']),
            'skipped' => count($plan['skipped']),
            'candidates' => $plan['candidates'],
            'skippedDocuments' => $plan['skipped'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function run(string $defaultLocale, ?string $migrationId = null, bool $confirmed = false): array
    {
        if (!$confirmed) {
            throw new FlatFileException('Migration run requires explicit confirmation (--yes).');
        }

        $defaultLocale = $this->assertLocale($defaultLocale);
        $inventory = $this->inventory();

        if ($inventory['blockingConflicts']) {
            throw new FlatFileException('Migration blocked by slug or locale-copy conflicts. Resolve manually first.');
        }

        $plan = $this->buildConversionPlan($inventory['documents'], $inventory['conflicts'], $defaultLocale);
        if ($plan['candidates'] === []) {
            return [
                'migrationId' => null,
                'converted' => 0,
                'message' => 'No legacy documents require migration.',
            ];
        }

        $migrationId = $migrationId !== null && $migrationId !== ''
            ? $this->sanitizeMigrationId($migrationId)
            : $this->proposeMigrationId();

        $migrationRoot = self::MIGRATION_DIR . '/' . $migrationId;
        $this->writer->createDirectory($migrationRoot);
        $this->writer->createDirectory($migrationRoot . '/files');

        $manifest = [
            'id' => $migrationId,
            'createdAt' => AppTimezone::nowIso8601(),
            'defaultLocale' => $defaultLocale,
            'status' => 'in_progress',
            'entries' => [],
        ];

        foreach ($plan['candidates'] as $candidate) {
            $path = (string) $candidate['path'];
            $rawBefore = $this->reader->read($path);
            $backupRelative = $migrationRoot . '/files/' . $path;
            $this->writer->createDirectory(dirname($backupRelative));
            $this->writer->write($backupRelative, $rawBefore, false);

            $content = $this->repository->findByPath($path);
            if ($content === null) {
                throw new FlatFileException('Failed to load content for migration: ' . $path);
            }

            $this->localizedWriter->upgradeLegacyToSchemaV2($content, $defaultLocale);
            $this->repository->save($content);

            $rawAfter = $this->reader->read($path);
            $manifest['entries'][] = [
                'path' => $path,
                'slug' => $content->getSlug(),
                'type' => $candidate['type'],
                'backupPath' => $backupRelative,
                'sha256Before' => hash('sha256', $rawBefore),
                'sha256After' => hash('sha256', $rawAfter),
                'schemaVersionBefore' => (int) ($candidate['schemaVersion'] ?? 1),
                'schemaVersionAfter' => 2,
                'status' => 'converted',
            ];
        }

        $manifest['status'] = 'completed';
        $manifest['completedAt'] = AppTimezone::nowIso8601();
        $this->writeManifest($migrationRoot, $manifest);

        $this->contentCache->purgeAll();
        $this->index->rebuild($this->repository);

        $verified = $this->verifyConvertedEntries($manifest['entries']);

        return [
            'migrationId' => $migrationId,
            'converted' => count($manifest['entries']),
            'manifestPath' => $migrationRoot . '/manifest.json',
            'verified' => $verified,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rollback(string $migrationId, bool $confirmed = false): array
    {
        if (!$confirmed) {
            throw new FlatFileException('Migration rollback requires explicit confirmation (--yes).');
        }

        $migrationId = $this->sanitizeMigrationId($migrationId);
        $migrationRoot = self::MIGRATION_DIR . '/' . $migrationId;
        $manifest = $this->readManifest($migrationRoot);

        if (($manifest['status'] ?? '') === 'rolled_back') {
            throw new FlatFileException('Migration already rolled back: ' . $migrationId);
        }

        $restored = 0;
        /** @var list<array<string, mixed>> $entries */
        $entries = is_array($manifest['entries'] ?? null) ? $manifest['entries'] : [];

        foreach ($entries as $entry) {
            $path = (string) ($entry['path'] ?? '');
            $backupPath = (string) ($entry['backupPath'] ?? '');
            if ($path === '' || $backupPath === '' || !$this->reader->exists($backupPath)) {
                throw new FlatFileException('Missing backup for rollback: ' . $backupPath);
            }

            $raw = $this->reader->read($backupPath);
            $this->writer->write($path, $raw, false);
            $restored++;
        }

        $manifest['status'] = 'rolled_back';
        $manifest['rolledBackAt'] = AppTimezone::nowIso8601();
        $this->writeManifest($migrationRoot, $manifest);

        $this->contentCache->purgeAll();
        $this->index->rebuild($this->repository);

        return [
            'migrationId' => $migrationId,
            'restored' => $restored,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function collectDocuments(): array
    {
        $documents = [];

        foreach (['pages' => 'page', 'blog' => 'article'] as $directory => $type) {
            foreach ($this->listContentFiles($directory) as $path) {
                $document = $this->describeDocument($path, $type);
                if ($document !== null) {
                    $documents[] = $document;
                }
            }
        }

        usort($documents, static fn (array $a, array $b): int => strcmp((string) $a['path'], (string) $b['path']));

        return $documents;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function describeDocument(string $path, string $type): ?array
    {
        try {
            $content = $this->repository->findByPath($path);
            if ($content === null) {
                return null;
            }

            /** @var array<string, mixed> $frontMatter */
            $frontMatter = $content->getFrontMatter();
            $schemaVersion = (int) ($frontMatter['schemaVersion'] ?? 1);
            /** @var array<string, mixed> $canonical */
            $canonical = $this->normalizer->normalize($content);
            $locales = array_keys($canonical['localizedContent']);
            $classification = $this->classifyDocument($schemaVersion, $frontMatter, $path);

            return [
                'path' => $path,
                'type' => $type,
                'slug' => $content->getSlug() !== '' ? $content->getSlug() : pathinfo($path, PATHINFO_FILENAME),
                'schemaVersion' => $schemaVersion,
                'defaultLocale' => (string) $canonical['defaultLocale'],
                'locales' => $locales,
                'classification' => $classification,
                'migratable' => $classification === 'legacy_single_locale',
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $frontMatter
     */
    private function classifyDocument(int $schemaVersion, array $frontMatter, string $path): string
    {
        if ($schemaVersion >= 2 && is_array($frontMatter['localizedContent'] ?? null)) {
            return 'schema_v2';
        }

        $basename = pathinfo($path, PATHINFO_FILENAME);
        if (preg_match('/^.+-[a-z]{2}$/', $basename) === 1) {
            return 'locale_copy_candidate';
        }

        return 'legacy_single_locale';
    }

    /**
     * @param list<array<string, mixed>> $documents
     * @return list<array<string, mixed>>
     */
    private function detectConflicts(array $documents): array
    {
        $conflicts = [];

        $bySlug = [];
        foreach ($documents as $document) {
            $key = ($document['type'] ?? '') . ':' . ($document['slug'] ?? '');
            $bySlug[$key][] = $document;
        }

        foreach ($bySlug as $key => $group) {
            if (count($group) > 1) {
                $conflicts[] = [
                    'reason' => 'slug_conflict',
                    'key' => $key,
                    'paths' => array_map(static fn (array $doc): string => (string) $doc['path'], $group),
                    'message' => 'Multiple files share the same slug; resolve manually before migration.',
                ];
            }
        }

        $pathsByDirAndBasename = [];
        foreach ($documents as $document) {
            $path = (string) $document['path'];
            $dir = dirname($path);
            $basename = pathinfo($path, PATHINFO_FILENAME);
            $pathsByDirAndBasename[$dir][$basename] = $path;
        }

        foreach ($documents as $document) {
            $path = (string) $document['path'];
            $dir = dirname($path);
            $basename = pathinfo($path, PATHINFO_FILENAME);
            if (preg_match('/^(.+)-([a-z]{2})$/', $basename, $matches) !== 1) {
                continue;
            }

            $baseName = $matches[1];
            if (!isset($pathsByDirAndBasename[$dir][$baseName])) {
                continue;
            }

            $conflicts[] = [
                'reason' => 'ambiguous_locale_copy',
                'paths' => [$pathsByDirAndBasename[$dir][$baseName], $path],
                'message' => 'Locale copy pair detected; automatic merge is prohibited.',
            ];
        }

        return $conflicts;
    }

    /**
     * @param list<array<string, mixed>> $documents
     * @param list<array<string, mixed>> $conflicts
     * @return array{candidates: list<array<string, mixed>>, skipped: list<array<string, mixed>>}
     */
    private function buildConversionPlan(array $documents, array $conflicts, string $defaultLocale): array
    {
        $blockedPaths = [];
        foreach ($conflicts as $conflict) {
            foreach ($conflict['paths'] as $path) {
                $blockedPaths[(string) $path] = true;
            }
        }

        $candidates = [];
        $skipped = [];

        foreach ($documents as $document) {
            if (($document['migratable'] ?? false) !== true) {
                $skipped[] = $document;
                continue;
            }

            if (isset($blockedPaths[(string) $document['path']])) {
                $skipped[] = array_merge($document, ['skipReason' => 'blocked_by_conflict']);
                continue;
            }

            $candidates[] = array_merge($document, [
                'targetDefaultLocale' => $defaultLocale,
                'targetSchemaVersion' => 2,
            ]);
        }

        return [
            'candidates' => $candidates,
            'skipped' => $skipped,
        ];
    }

    /**
     * @param list<array<string, mixed>> $documents
     * @param list<array<string, mixed>> $conflicts
     * @return array<string, int>
     */
    private function summarizeDocuments(array $documents, array $conflicts): array
    {
        $totals = [
            'documents' => count($documents),
            'legacySingleLocale' => 0,
            'schemaV2' => 0,
            'localeCopyCandidates' => 0,
            'conflicts' => count($conflicts),
        ];

        foreach ($documents as $document) {
            match ($document['classification'] ?? '') {
                'schema_v2' => $totals['schemaV2']++,
                'locale_copy_candidate' => $totals['localeCopyCandidates']++,
                default => $totals['legacySingleLocale']++,
            };
        }

        return $totals;
    }

    /**
     * @param list<array<string, mixed>> $entries
     * @return list<array<string, mixed>>
     */
    private function verifyConvertedEntries(array $entries): array
    {
        $results = [];

        foreach ($entries as $entry) {
            $path = (string) ($entry['path'] ?? '');
            $content = $this->repository->findByPath($path);
            $ok = $content instanceof Content
                && (int) ($content->getFrontMatter()['schemaVersion'] ?? 0) === 2
                && is_array($content->getFrontMatter()['localizedContent'] ?? null);

            $results[] = [
                'path' => $path,
                'ok' => $ok,
            ];
        }

        return $results;
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function writeManifest(string $migrationRoot, array $manifest): void
    {
        $this->writer->write(
            $migrationRoot . '/manifest.json',
            JsonHelper::encode($manifest),
            false
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function readManifest(string $migrationRoot): array
    {
        $manifestPath = $migrationRoot . '/manifest.json';
        if (!$this->reader->exists($manifestPath)) {
            throw new FlatFileException('Migration manifest not found: ' . $manifestPath);
        }

        $raw = $this->reader->read($manifestPath);
        /** @var array<string, mixed> $decoded */
        $decoded = JsonHelper::decode($raw);

        return $decoded;
    }

    /**
     * @return list<string>
     */
    private function listContentFiles(string $directory): array
    {
        try {
            return array_merge(
                $this->reader->listFiles($directory, '*.md'),
                $this->reader->listFiles($directory, '*.json')
            );
        } catch (\Throwable) {
            return [];
        }
    }

    private function siteDefaultLocale(): string
    {
        $locale = strtolower(trim((string) $this->settings->get('general.language', 'sk')));

        return preg_match('/^[a-z]{2}$/', $locale) === 1 ? $locale : 'sk';
    }

    private function assertLocale(string $locale): string
    {
        $locale = strtolower(trim($locale));
        if (preg_match('/^[a-z]{2}$/', $locale) !== 1) {
            throw new FlatFileException('Invalid locale code: ' . $locale);
        }

        return $locale;
    }

    private function proposeMigrationId(): string
    {
        return 'locale-v2-' . date('Ymd-His');
    }

    private function sanitizeMigrationId(string $migrationId): string
    {
        $migrationId = trim($migrationId);
        if ($migrationId === '' || preg_match('/^[a-zA-Z0-9._-]+$/', $migrationId) !== 1) {
            throw new FlatFileException('Invalid migration id: ' . $migrationId);
        }

        return $migrationId;
    }
}
