<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Import;

/**
 * PHPStan shape for CMS import rows (It.80g+).
 *
 * @phpstan-type NormalizedImportRow array{
 *     type: string,
 *     slug: string,
 *     title: string,
 *     content: string,
 *     status: string,
 *     date: string,
 *     description: string,
 *     tags: list<string>,
 *     importSource: string
 * }
 */
interface ImportRowTypes
{
}
