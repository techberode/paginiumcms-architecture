<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Contracts;

interface MarkdownParserInterface
{
    /**
     * @return array<int|string, mixed>
     */
    public function parse(string $content): array;
    /**
     * @param array<int|string, mixed> $frontMatter
     */
    public function serialize(array $frontMatter, string $content): string;
    /**
     * @return array<int|string, mixed>
     */
    public function extractFrontMatter(string $content): array;
    public function extractContent(string $content): string;
    public function toHtml(string $markdown): string;
}
