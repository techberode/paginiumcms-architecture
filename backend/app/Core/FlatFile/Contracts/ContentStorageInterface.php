<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Contracts;

use PaginiumCMS\Core\FlatFile\Models\Content;
use PaginiumCMS\Http\Support\PaginationQuery;

/**
 * Abstrakcia formátu úložiska obsahu – Markdown alebo JSON (Iterácia 19).
 */
interface ContentStorageInterface
{
    public function format(): string;

    /**
     * @return array{frontMatter: array<string, mixed>, content: string, html: string}
     */
    public function parse(string $raw): array;

    /**
     * @param array<string, mixed> $frontMatter
     */
    public function serialize(array $frontMatter, string $content): string;

    public function extension(): string;

    public function buildPath(string $directory, string $slug): string;
}
