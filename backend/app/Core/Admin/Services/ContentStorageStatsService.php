<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Admin\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Veľkosť flat-file obsahu pre admin dashboard (It.20).
 */
final class ContentStorageStatsService
{
    public function __construct(
        private FileReaderInterface $reader
    ) {
    }

    /**
     * @return array{
     *     total_bytes: int,
     *     total_human: string,
     *     document_count: int,
     *     pages: int,
     *     articles: int,
     *     media: int,
     *     users: int
     * }
     */
    public function summarize(int $pages, int $articles, int $media, int $users): array
    {
        $base = rtrim($this->reader->getBasePath(), '/');
        $pageScan = $this->scanDirectory($base . '/pages');
        $articleScan = $this->scanDirectory($base . '/blog');
        $mediaScan = $this->scanDirectory($base . '/media');
        $userScan = $this->scanDirectory($base . '/data/users');

        $totalBytes = $pageScan['bytes'] + $articleScan['bytes'] + $mediaScan['bytes'];
        $documentCount = $pages + $articles;

        return [
            'total_bytes' => $totalBytes,
            'total_human' => $this->formatBytes($totalBytes),
            'document_count' => $documentCount,
            'pages' => $pages,
            'articles' => $articles,
            'media' => $media,
            'users' => $users,
        ];
    }

    /**
     * @return array{count: int, bytes: int}
     */
    private function scanDirectory(string $absolutePath): array
    {
        if (!is_dir($absolutePath)) {
            return ['count' => 0, 'bytes' => 0];
        }

        $count = 0;
        $bytes = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($absolutePath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            ++$count;
            $bytes += (int) $file->getSize();
        }

        return ['count' => $count, 'bytes' => $bytes];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1, '.', '') . ' KB';
        }
        if ($bytes < 1024 * 1024 * 1024) {
            return number_format($bytes / (1024 * 1024), 1, '.', '') . ' MB';
        }

        return number_format($bytes / (1024 * 1024 * 1024), 2, '.', '') . ' GB';
    }
}
