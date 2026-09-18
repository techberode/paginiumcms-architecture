<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\HybridEngine\QueryIndex;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use RuntimeException;

/**
 * Safe path resolution for derived SQLite index (It.92b).
 */
final class QueryIndexPaths
{
    public function __construct(
        private FileReaderInterface $reader,
        private string $relativeFile = 'data/index/content.sqlite'
    ) {
    }

    public function absolutePath(): string
    {
        $base = realpath($this->reader->getBasePath());
        if ($base === false) {
            throw new RuntimeException('Query index base path is not readable.');
        }

        $relative = ltrim($this->relativeFile, '/');
        $candidate = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $dirReal = realpath(dirname($candidate));
        if ($dirReal === false) {
            $indexDir = dirname($candidate);
            if (!is_dir($indexDir) && !mkdir($indexDir, 0755, true) && !is_dir($indexDir)) {
                throw new RuntimeException('Query index directory is not writable.');
            }
            $dirReal = realpath($indexDir);
        }

        if ($dirReal === false || !str_starts_with($dirReal . DIRECTORY_SEPARATOR, $base . DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('Query index path escapes storage base.');
        }

        return $dirReal . DIRECTORY_SEPARATOR . basename($candidate);
    }

    public function indexDirectory(): string
    {
        return dirname($this->absolutePath());
    }
}
