<?php
// backend/app/Core/Versioning/Services/EnhancedVersionManager.php

declare(strict_types=1);

namespace PaginiumCMS\Core\Versioning\Services;

use PaginiumCMS\Core\Versioning\Models\Version;
use PaginiumCMS\Core\Versioning\Contracts\VersionableInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\CodeEditor\Services\CodeEditorLogger;
use PaginiumCMS\Core\CodeEditor\Services\DiffGenerator;
use PaginiumCMS\Support\FileHelper;
use PaginiumCMS\Support\JsonHelper;
use PaginiumCMS\Support\Lang;

class EnhancedVersionManager implements VersionableInterface
{
    private FileReaderInterface $reader;
    private FileWriterInterface $writer;
    private DiffGenerator $diffGenerator;
    private CodeEditorLogger $logger;
    private string $storagePath;
    private int $maxVersions;
    /** @var array<int|string, mixed> */
    private array $versionMetadata = [];

    public function __construct(
        FileReaderInterface $reader,
        FileWriterInterface $writer,
        DiffGenerator $diffGenerator,
        CodeEditorLogger $logger,
        string $storagePath = 'data/versions',
        int $maxVersions = 50
    ) {
        $this->reader = $reader;
        $this->writer = $writer;
        $this->diffGenerator = $diffGenerator;
        $this->logger = $logger;
        $this->storagePath = rtrim($storagePath, '/');
        $this->maxVersions = $maxVersions;
    }

    public function createVersion(string $contentId, string $contentType, string $content, string $frontMatter, string $userId, string $message = ''): Version
    {
        $version = new Version();
        $version->setContentId($contentId);
        $version->setContentType($contentType);
        $version->setContent($content);
        $version->setFrontMatter($frontMatter);
        $version->setCreatedBy($userId);
        $version->setMessage($message);

        $lastVersion = $this->getLastVersion($contentId);
        if ($lastVersion) {
            $version->setVersion($lastVersion->getVersion() + 1);
            
            // Generovanie podrobného diffu
            $diff = $this->diffGenerator->generateDetailedDiff(
                $lastVersion->getContent(),
                $content,
                $lastVersion->getFrontMatter(),
                $frontMatter
            );
            $version->setDiff($diff);
            
            // Uloženie metadát o zmene
            $this->versionMetadata[$contentId] = [
                'last_version' => $lastVersion->getVersion(),
                'last_change' => date('Y-m-d H:i:s'),
                'change_summary' => $this->summarizeDiff($diff)
            ];
        }

        $this->saveVersion($version);
        $this->cleanOldVersions($contentId);
        
        // Logovanie verzie
        $this->logger->logVersion(
            $contentId,
            $version->getVersion(),
            'created',
            [
                'user_id' => $userId,
                'message' => $message,
                'content_type' => $contentType
            ]
        );

        return $version;
    }

    public function getVersion(string $contentId, int $version): ?Version
    {
        $path = $this->getVersionPath($contentId, $version);
        try {
            $content = $this->reader->read($path);
            $data = json_decode($content, true);
            if (!is_array($data)) {
                return null;
            }

            return $this->hydrate($data);
        } catch (FlatFileException) {
            return null;
        }
    }

    public function getLastVersion(string $contentId): ?Version
    {
        $versions = $this->getVersions($contentId);
        return empty($versions) ? null : $versions[0];
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getVersions(string $contentId): array
    {
        $versions = [];
        $pattern = $this->getFullPath('') . $contentId . '_*.json';
        $files = glob($pattern);
        if ($files === false) {
            return $versions;
        }

        foreach ($files as $file) {
            try {
                $data = JsonHelper::decode(FileHelper::read($file));
                $versions[] = $this->hydrate($data);
            } catch (\Exception) {
                continue;
            }
        }

        usort($versions, function ($a, $b) {
            return $b->getVersion() - $a->getVersion();
        });

        return $versions;
    }

    public function restoreVersion(string $contentId, int $version): bool
    {
        $versionData = $this->getVersion($contentId, $version);
        if (!$versionData) {
            return false;
        }

        // Vytvorenie novej verzie z obnovenej
        $this->createVersion(
            $contentId,
            $versionData->getContentType(),
            $versionData->getContent(),
            $versionData->getFrontMatter(),
            'system',
            'Obnova na verziu ' . $version
        );

        $this->logger->logVersion(
            $contentId,
            $version,
            'restored',
            [
                'restored_by' => 'system',
                'restored_from' => $version
            ]
        );

        return true;
    }

    public function deleteVersions(string $contentId, int $keep = 10): int
    {
        $versions = $this->getVersions($contentId);
        $deleted = 0;

        foreach (array_slice($versions, $keep) as $version) {
            $path = $this->getVersionPath($contentId, $version->getVersion());
            if (file_exists($path)) {
                unlink($path);
                $deleted++;
                
                $this->logger->logVersion(
                    $contentId,
                    $version->getVersion(),
                    'deleted',
                    ['deleted_by' => 'system']
                );
            }
        }

        return $deleted;
    }

    /**
     * @return array<int|string, mixed>|null
     */
    public function getDiff(string $contentId, int $from, int $to): ?array
    {
        $fromVersion = $this->getVersion($contentId, $from);
        $toVersion = $this->getVersion($contentId, $to);

        if (!$fromVersion || !$toVersion) {
            return null;
        }

        return $this->diffGenerator->generateDetailedDiff(
            $fromVersion->getContent(),
            $toVersion->getContent(),
            $fromVersion->getFrontMatter(),
            $toVersion->getFrontMatter()
        );
    }

    /**
     * Získa históriu zmien pre obsah
 * @return array<int|string, mixed>
 */public function getVersionHistory(string $contentId): array
    {
        $versions = $this->getVersions($contentId);
        $history = [];

        foreach ($versions as $version) {
            $history[] = [
                'version' => $version->getVersion(),
                'created_at' => $version->getCreatedAt(),
                'created_by' => $version->getCreatedBy(),
                'message' => $version->getMessage(),
                'diff_summary' => $this->summarizeDiff($version->getDiff()),
                'size' => strlen($version->getContent())
            ];
        }

        return $history;
    }

    /**
     * Porovná dve verzie a vráti podrobný rozdiel
 * @return array<int|string, mixed>
 */public function compareVersions(string $contentId, int $version1, int $version2): array
    {
        $v1 = $this->getVersion($contentId, $version1);
        $v2 = $this->getVersion($contentId, $version2);

        if (!$v1 || !$v2) {
            return ['error' => 'Version not found'];
        }

        return [
            'version1' => [
                'number' => $version1,
                'timestamp' => $v1->getCreatedAt(),
                'author' => $v1->getCreatedBy()
            ],
            'version2' => [
                'number' => $version2,
                'timestamp' => $v2->getCreatedAt(),
                'author' => $v2->getCreatedBy()
            ],
            'diff' => $this->diffGenerator->generateDetailedDiff(
                $v1->getContent(),
                $v2->getContent(),
                $v1->getFrontMatter(),
                $v2->getFrontMatter()
            ),
            'summary' => $this->summarizeDiff(
                $this->diffGenerator->generateDetailedDiff(
                    $v1->getContent(),
                    $v2->getContent(),
                    $v1->getFrontMatter(),
                    $v2->getFrontMatter()
                )
            )
        ];
    }

    /**
     * Získa štatistiky verzovania
 * @return array<int|string, mixed>
 */public function getVersionStats(): array
    {
        $stats = [
            'total_versions' => 0,
            'total_content_items' => 0,
            'by_type' => [],
            'recent_versions' => [],
            'largest_files' => []
        ];

        $contentIds = $this->getAllContentIds();
        $stats['total_content_items'] = count($contentIds);

        foreach ($contentIds as $id) {
            $versions = $this->getVersions($id);
            $stats['total_versions'] += count($versions);
            
            if (!empty($versions)) {
                $type = $versions[0]->getContentType();
                $stats['by_type'][$type] = ($stats['by_type'][$type] ?? 0) + 1;
                
                // Posledných 5 verzií
                $stats['recent_versions'] = array_merge(
                    $stats['recent_versions'],
                    array_slice($versions, 0, 5)
                );
                
                // Najväčšie súbory
                foreach ($versions as $version) {
                    $size = strlen($version->getContent());
                    $stats['largest_files'][] = [
                        'content_id' => $id,
                        'version' => $version->getVersion(),
                        'size' => $size,
                        'type' => $type
                    ];
                }
            }
        }

        // Zoradenie a obmedzenie
        usort($stats['largest_files'], fn($a, $b) => $b['size'] - $a['size']);
        $stats['largest_files'] = array_slice($stats['largest_files'], 0, 10);
        usort($stats['recent_versions'], fn($a, $b) => 
            strtotime($b->getCreatedAt()) - strtotime($a->getCreatedAt())
        );
        $stats['recent_versions'] = array_slice($stats['recent_versions'], 0, 10);

        return $stats;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getVersionMetadata(): array
    {
        return $this->versionMetadata;
    }

    private function saveVersion(Version $version): void
    {
        $path = $this->getVersionPath($version->getContentId(), $version->getVersion());
        $this->ensureDirectoryExists($path);
        $this->writer->write(
            $path,
            JsonHelper::encode($version->toArray(), JSON_PRETTY_PRINT)
        );
    }

    /**
     * @param array<int|string, mixed> $data
     */
    private function hydrate(array $data): Version
    {
        $version = new Version();
        // Hydratácia verzie z dát
        $reflection = new \ReflectionClass($version);
        foreach ($data as $key => $value) {
            if ($key === 'id') {
                $prop = $reflection->getProperty('id');
                $prop->setValue($version, $value);
            } elseif ($key === 'contentId') {
                $version->setContentId($value);
            } elseif ($key === 'contentType') {
                $version->setContentType($value);
            } elseif ($key === 'version') {
                $version->setVersion($value);
            } elseif ($key === 'content') {
                $version->setContent($value);
            } elseif ($key === 'frontMatter') {
                $version->setFrontMatter($value);
            } elseif ($key === 'createdBy') {
                $version->setCreatedBy($value);
            } elseif ($key === 'message') {
                $version->setMessage($value);
            } elseif ($key === 'diff') {
                $version->setDiff($value);
            }
        }
        return $version;
    }

    private function getVersionPath(string $contentId, int $version): string
    {
        return $this->storagePath . '/' . $contentId . '_' . $version . '.json';
    }

    private function getFullPath(string $path): string
    {
        return $this->reader->getBasePath() . '/' . $this->storagePath . '/' . $path;
    }

    private function ensureDirectoryExists(string $path): void
    {
        $fullPath = dirname($this->reader->getBasePath() . '/' . $path);
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
    }

    private function cleanOldVersions(string $contentId): void
    {
        $this->deleteVersions($contentId, $this->maxVersions);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function getAllContentIds(): array
    {
        $pattern = $this->getFullPath('') . '*_*.json';
        $files = glob($pattern);
        if ($files === false) {
            return [];
        }
        $ids = [];

        foreach ($files as $file) {
            $basename = basename($file);
            if (preg_match('/^(.+)_\d+\.json$/', $basename, $matches)) {
                $ids[] = $matches[1];
            }
        }

        return array_unique($ids);
    }

    /**
     * @param array<int|string, mixed> $diff
     */
    private function summarizeDiff(?array $diff): string
    {
        if (!$diff) {
            return Lang::get('diff.no_changes', [], 'audit');
        }

        $summary = [];

        if (isset($diff['additions']) && $diff['additions'] > 0) {
            $summary[] = Lang::get('diff.added', ['count' => (string) $diff['additions']], 'audit');
        }
        if (isset($diff['deletions']) && $diff['deletions'] > 0) {
            $summary[] = Lang::get('diff.removed', ['count' => (string) $diff['deletions']], 'audit');
        }
        if (isset($diff['modifications']) && $diff['modifications'] > 0) {
            $summary[] = Lang::get('diff.modified', ['count' => (string) $diff['modifications']], 'audit');
        }

        return $summary === []
            ? Lang::get('diff.no_significant', [], 'audit')
            : implode(', ', $summary);
    }
}
