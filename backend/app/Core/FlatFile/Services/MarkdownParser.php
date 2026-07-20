<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Services;

use PaginiumCMS\Core\FlatFile\Contracts\MarkdownParserInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FrontMatterParserInterface;
use PaginiumCMS\Core\Editor\Services\ContentBodyRenderer;

/**
 * Kompletný parser pre Markdown súbory s Front Matter.
 *
 * Kombinuje FrontMatterParser a MarkdownContentParser.
 */
class MarkdownParser implements MarkdownParserInterface
{
    private FrontMatterParserInterface $frontMatterParser;
    private ContentBodyRenderer $bodyRenderer;

    public function __construct(
        FrontMatterParserInterface $frontMatterParser,
        ContentBodyRenderer $bodyRenderer
    ) {
        $this->frontMatterParser = $frontMatterParser;
        $this->bodyRenderer = $bodyRenderer;
    }

    /**
     * {@inheritDoc}
 * @return array<int|string, mixed>
 */public function parse(string $content): array
    {
        $frontMatter = $this->frontMatterParser->parse($content);
        $markdown = $this->frontMatterParser->extractContent($content);
        $contentFormat = $this->bodyRenderer->normalizeContentFormat(
            $frontMatter['contentFormat'] ?? null,
            $markdown
        );

        return [
            'frontMatter' => $frontMatter,
            'content' => $markdown,
            'html' => $this->bodyRenderer->resolveHtml($markdown, $contentFormat),
        ];
    }

    /**
     * {@inheritDoc}
 * @param array<int|string, mixed> $frontMatter
 */public function serialize(array $frontMatter, string $content): string
    {
        $serializedFrontMatter = $this->frontMatterParser->serialize($frontMatter);
        return $serializedFrontMatter . $content;
    }

    /**
     * {@inheritDoc}
 * @return array<int|string, mixed>
 */public function extractFrontMatter(string $content): array
    {
        return $this->frontMatterParser->extractFrontMatter($content);
    }

    /**
     * {@inheritDoc}
     */
    public function extractContent(string $content): string
    {
        return $this->frontMatterParser->extractContent($content);
    }

    /**
     * {@inheritDoc}
     */
    public function toHtml(string $markdown): string
    {
        return $this->bodyRenderer->resolveHtml($markdown, 'markdown');
    }
}
