<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Versioning\Services;

use PaginiumCMS\Core\Versioning\Models\Version;
use PaginiumCMS\Core\Versioning\Contracts\VersionableInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;

class VersionManager implements VersionableInterface
{
    private FileReaderInterface $reader;
    private FileWriterInterface $writer;
    private DiffGenerator $diffGenerator;
    private string $storagePath;
    private int $maxVersions;

    public function __construct(
        FileReaderInterface $reader,
        FileWriterInterface $writer,
        DiffGenerator $diffGenerator,
        string $storagePath = 'data/versions',
        int $maxVersions = 50
    ) {
        $this->reader = $reader;
        $this->writer = $writer;
        $this->diffGenerator = $diffGenerator;
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
            $diff = $this->diffGenerator->generate($lastVersion->getContent(), $content);
            $version->setDiff($diff);
        }

        $this->saveVersion($version);
        $this->cleanOldVersions($contentId);

        return $version;
    }

    public function getVersion(string $contentId, int $version): ?Version
    {
        $path = $this->getVersionPath($contentId, $version);
        try {
            $content = $this->reader->read($path);
            $data = json_decode($content, true);
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

    public function getVersions(string $contentId): array
    {
        $versions = [];
        $pattern = $this->getFullPath('') . $contentId . '_*.json';
        $files = glob($pattern);

        foreach ($files as $file) {
            try {
                $content = file_get_contents($file);
                $data = json_decode($content, true);
                if ($data) {
                    $versions[] = $this->hydrate($data);
                }
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

        $this->createVersion(
            $contentId,
            $versionData->getContentType(),
            $versionData->getContent(),
            $versionData->getFrontMatter(),
            'system',
            'Obnova na verziu ' . $version
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
            }
        }

        return $deleted;
    }

    public function getDiff(string $contentId, int $from, int $to): ?array
    {
        $fromVersion = $this->getVersion($contentId, $from);
        $toVersion = $this->getVersion($contentId, $to);

        if (!$fromVersion || !$toVersion) {
            return null;
        }

        return $this->diffGenerator->generate($fromVersion->getContent(), $toVersion->getContent());
    }

    private function saveVersion(Version $version): void
    {
        $path = $this->getVersionPath($version->getContentId(), $version->getVersion());
        $this->ensureDirectoryExists($path);
        $this->writer->write($path, json_encode($version->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function hydrate(array $data): Version
    {
        $version = new Version();
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
}
