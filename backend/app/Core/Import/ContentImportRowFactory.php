<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Import;

/**
 * Builds normalized import rows for CMS parsers.
 *
 * @phpstan-import-type NormalizedImportRow from ImportRowTypes
 */
final class ContentImportRowFactory
{
    /**
     * @param list<string> $tags
     * @return NormalizedImportRow
     */
    public static function create(
        string $type,
        string $slug,
        string $title,
        string $content,
        string $status,
        string $date,
        string $description,
        array $tags,
        string $importSource,
    ): array {
        return [
            'type' => $type,
            'slug' => $slug,
            'title' => $title,
            'content' => $content,
            'status' => $status,
            'date' => $date,
            'description' => $description,
            'tags' => $tags,
            'importSource' => $importSource,
        ];
    }
}
