<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Import;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;

/**
 * Routes CMS import formats to parsers (It.80g phase 2).
 *
 * @phpstan-import-type NormalizedImportRow from ImportRowTypes
 */
final class ContentImportSourceRegistry
{
    public function __construct(
        private WordPressWxrImporter $wordpress,
        private GravPagesImporter $grav,
        private JekyllSiteImporter $jekyll,
        private HugoSiteImporter $hugo,
        private GhostJsonImporter $ghost,
        private ContentMigrationArchiveExtractor $archives,
    ) {
    }

    /**
     * @return list<array{id: string, label: string, input: string, hint: string}>
     */
    public function listSources(): array
    {
        return [
            [
                'id' => 'wordpress',
                'label' => 'WordPress (WXR XML)',
                'input' => 'file',
                'hint' => 'Tools → Export → All content (WXR). Upload .xml or a ZIP containing the export.',
            ],
            [
                'id' => 'grav',
                'label' => 'Grav CMS',
                'input' => 'directory',
                'hint' => 'Zip the Grav user/pages folder (or full user/ directory). CLI: --format=grav --path=/site/user/pages',
            ],
            [
                'id' => 'jekyll',
                'label' => 'Jekyll',
                'input' => 'directory',
                'hint' => 'Site root with _posts/ (and optional _pages/). Zip the repository root.',
            ],
            [
                'id' => 'hugo',
                'label' => 'Hugo',
                'input' => 'directory',
                'hint' => 'Zip the Hugo content/ directory or site root containing content/.',
            ],
            [
                'id' => 'ghost',
                'label' => 'Ghost',
                'input' => 'file',
                'hint' => 'Ghost Admin → Labs → Export content (JSON). Upload .json or ZIP.',
            ],
            [
                'id' => 'json',
                'label' => 'Paginium JSON export',
                'input' => 'file',
                'hint' => 'Bundle from content:export for re-import or staging merge.',
            ],
        ];
    }

    /**
     * @return list<NormalizedImportRow>
     */
    public function parse(string $format, string $path): array
    {
        $format = strtolower(trim($format));

        return match ($format) {
            'wordpress', 'wxr', 'xml' => $this->wordpress->parseFile($path),
            'grav' => $this->grav->parseDirectory($path),
            'jekyll' => $this->jekyll->parseDirectory($path),
            'hugo' => $this->hugo->parseDirectory($path),
            'ghost' => $this->ghost->parseFile($path),
            default => throw new FlatFileException('Unsupported import format: ' . $format),
        };
    }

    /**
     * @return array{format: string, path: string, cleanupDir: string|null}
     */
    public function resolveUploadPath(string $format, string $uploadedPath, string $clientFilename): array
    {
        $format = strtolower(trim($format));
        $extension = strtolower(pathinfo($clientFilename, PATHINFO_EXTENSION));

        if ($extension === 'zip') {
            $extracted = $this->archives->extractZip($uploadedPath);
            $detectedFormat = $format !== '' && $format !== 'auto' ? $format : $extracted['format'];

            return [
                'format' => $detectedFormat,
                'path' => $extracted['importPath'],
                'cleanupDir' => $extracted['extractDir'],
            ];
        }

        if ($format === '' || $format === 'auto') {
            if ($extension === 'xml') {
                $format = 'wordpress';
            } elseif ($extension === 'json') {
                $format = 'ghost';
            } else {
                throw new FlatFileException('Specify --format or upload .xml / .json / .zip');
            }
        }

        return [
            'format' => $format,
            'path' => $uploadedPath,
            'cleanupDir' => null,
        ];
    }

    public function cleanup(?string $extractDir): void
    {
        if ($extractDir !== null && $extractDir !== '') {
            $this->archives->removeDirectory($extractDir);
        }
    }
}
