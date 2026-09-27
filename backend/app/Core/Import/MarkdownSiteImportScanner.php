<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Import;

use PaginiumCMS\Core\FlatFile\Contracts\FrontMatterParserInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;

/**
 * Recursively collects Markdown files with parsed YAML front matter.
 */
final class MarkdownSiteImportScanner
{
    public function __construct(
        private FrontMatterParserInterface $frontMatter,
    ) {
    }

    /**
     * @return list<array{
     *     relativePath: string,
     *     frontMatter: array<string, mixed>,
     *     body: string
     * }>
     */
    public function scanDirectory(string $root): array
    {
        $root = rtrim($root, '/\\');
        if (!is_dir($root)) {
            throw new FlatFileException('Import directory is not readable: ' . $root);
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $basename = $file->getBasename();
            if (!str_ends_with(strtolower($basename), '.md') && !str_ends_with(strtolower($basename), '.markdown')) {
                continue;
            }

            $absolute = $file->getPathname();
            $relative = ltrim(str_replace('\\', '/', substr($absolute, strlen($root))), '/');
            $raw = file_get_contents($absolute);
            if (!is_string($raw) || trim($raw) === '') {
                continue;
            }

            try {
                /** @var array<string, mixed> $meta */
                $meta = $this->frontMatter->parse($raw);
            } catch (\Throwable) {
                continue;
            }

            $body = $this->frontMatter->extractContent($raw);

            $files[] = [
                'relativePath' => $relative,
                'frontMatter' => $meta,
                'body' => $body,
            ];
        }

        usort(
            $files,
            static fn (array $left, array $right): int => strcmp($left['relativePath'], $right['relativePath'])
        );

        return $files;
    }
}
