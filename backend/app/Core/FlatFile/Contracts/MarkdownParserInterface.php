<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Contracts;

interface MarkdownParserInterface
{
    public function parse(string $content): array;
    public function serialize(array $frontMatter, string $content): string;
    public function extractFrontMatter(string $content): array;
    public function extractContent(string $content): string;
    public function toHtml(string $markdown): string;
}
