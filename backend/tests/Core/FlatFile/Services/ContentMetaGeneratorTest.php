<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\FlatFile\Services;

use PaginiumCMS\Core\FlatFile\Services\ContentMetaGenerator;
use PaginiumCMS\Core\FlatFile\Services\MarkdownContentParser;
use PHPUnit\Framework\TestCase;

final class ContentMetaGeneratorTest extends TestCase
{
    private ContentMetaGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new ContentMetaGenerator(new MarkdownContentParser());
    }

    public function testSuggestTagsReturnsSensibleSkKeywords(): void
    {
        $title = 'PaginiumCMS flat-file CMS pre malé firmy';
        $body = <<<'MD'
# Úvod

PaginiumCMS je moderný flat-file content management system bez SQL databázy.
Riešenie využíva PHP backend a React administráciu. Vhodné pre blogy a firemné weby.

## Výhody

Rýchle nasadenie, jednoduchá údržba a verziovanie obsahu v Git repozitári.
MD;

        $tags = $this->generator->suggestTags($title, $body, 'markdown', 8);

        $this->assertNotEmpty($tags);
        $lower = array_map(static fn (string $tag): string => mb_strtolower($tag), $tags);
        $this->assertContains('paginiumcms', $lower);
        $this->assertContains('flat-file', $lower);
        $this->assertLessThanOrEqual(8, count($tags));
    }

    public function testSuggestTagsSkipsExistingTags(): void
    {
        $tags = $this->generator->suggestTags(
            'Test článok o Docker',
            'Docker compose a nginx reverse proxy pre produkčné nasadenie.',
            'markdown',
            5,
            ['docker']
        );

        $lower = array_map(static fn (string $tag): string => mb_strtolower($tag), $tags);
        $this->assertNotContains('docker', $lower);
    }

    public function testSuggestDescriptionRespectsMaxLength(): void
    {
        $body = str_repeat('Toto je testovacia veta o PaginiumCMS. ', 20);
        $description = $this->generator->suggestDescription('Titulok', $body, 'markdown', 155);

        $this->assertLessThanOrEqual(160, mb_strlen($description));
        $this->assertStringContainsString('PaginiumCMS', $description);
    }

    public function testExtractPlainTextFromTiptapJson(): void
    {
        $json = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Ahoj svet"}]}]}';
        $plain = $this->generator->extractPlainText($json, 'tiptap_json');

        $this->assertSame('Ahoj svet', $plain);
    }

    public function testSuggestDescriptionUsesTitleWhenBodyEmpty(): void
    {
        $description = $this->generator->suggestDescription('Krátky titulok', '', 'markdown', 155);

        $this->assertSame('Krátky titulok', $description);
    }
}
