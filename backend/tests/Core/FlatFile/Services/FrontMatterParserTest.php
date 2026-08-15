<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\FlatFile\Services;

use PaginiumCMS\Core\FlatFile\Services\FrontMatterParser;
use PaginiumCMS\Core\FlatFile\Exception\InvalidFrontMatterException;
use PHPUnit\Framework\TestCase;

class FrontMatterParserTest extends TestCase
{
    private FrontMatterParser $parser;

    protected function setUp(): void
    {
        $this->parser = new FrontMatterParser();
    }

    public function testParseValidFrontMatter(): void
    {
        $content = <<<MD
        ---
        title: Test Page
        slug: test-page
        status: published
        ---
        # Content
        MD;

        $frontMatter = $this->parser->parse($content);

        $this->assertEquals('Test Page', $frontMatter['title']);
        $this->assertEquals('test-page', $frontMatter['slug']);
        $this->assertEquals('published', $frontMatter['status']);
    }

    public function testParseWithoutFrontMatter(): void
    {
        $content = '# Just content';
        $frontMatter = $this->parser->parse($content);

        $this->assertEmpty($frontMatter);
    }

    public function testParseInvalidYaml(): void
    {
        $content = <<<MD
        ---
        title: "Test
        invalid: yaml
        ---
        # Content
        MD;

        $this->expectException(InvalidFrontMatterException::class);
        $this->parser->parse($content);
    }

    public function testExtractFrontMatter(): void
    {
        $content = <<<MD
        ---
        title: Test
        ---
        # Content
        MD;

        $frontMatter = $this->parser->extractFrontMatter($content);

        $this->assertEquals('Test', $frontMatter['title']);
    }

    public function testExtractContent(): void
    {
        $content = <<<MD
        ---
        title: Test
        ---
        # Content
        MD;

        $extracted = $this->parser->extractContent($content);

        $this->assertEquals('# Content', $extracted);
    }

    public function testSerialize(): void
    {
        $frontMatter = [
            'title' => 'Test Page',
            'slug' => 'test-page',
        ];

        $serialized = $this->parser->serialize($frontMatter);

        $this->assertStringContainsString('---', $serialized);
        $this->assertStringContainsString('title', $serialized);
        $this->assertStringContainsString('Test Page', $serialized);
        $this->assertStringContainsString('slug', $serialized);
        $this->assertStringContainsString('test-page', $serialized);
    }

    public function testHasFrontMatter(): void
    {
        $content = "---\ntitle: Test\n---\n# Content";
        $this->assertTrue($this->parser->hasFrontMatter($content));

        $content = "# Content without front matter";
        $this->assertFalse($this->parser->hasFrontMatter($content));
    }

    /**
     * Regresia (betas 47–53): telo obsahujúce Markdown `---` oddeľovač sa NESMIE
     * považovať za koniec front matteru. Skôr `strpos('---')` našiel odsadený
     * `---` vnútri YAML `body: |` (schema v2 localizedContent) aj `---` v samotnom
     * tele → zvyšok YAML pretiekol do obsahu a rozbil články.
     */
    public function testClosingDelimiterIgnoresHorizontalRuleInBody(): void
    {
        $body = "# Nadpis\n\nOdsek jeden.\n\n---\n\nOdsek dva pod čiarou.";
        $content = "---\ntitle: Release\nslug: release\nstatus: draft\n---\n" . $body;

        $frontMatter = $this->parser->parse($content);
        $this->assertSame('release', $frontMatter['slug']);
        $this->assertSame('draft', $frontMatter['status']);

        $extracted = $this->parser->extractContent($content);
        $this->assertSame($body, $extracted);
        $this->assertStringNotContainsString('slug:', $extracted);
        $this->assertStringNotContainsString('status:', $extracted);
    }

    /**
     * Regresia: front matter s vnoreným multi-line literal blokom (schema v2
     * `localizedContent.*.body`), ktorý sám obsahuje odsadený `---`, sa musí
     * korektne rozdeliť – uzatvárací delimiter je len `---` na začiatku riadku.
     */
    public function testNestedLiteralBlockWithDashesRoundTrips(): void
    {
        $body = "# Beta\n\n**Verzia:** v1\n\n---\n\nText pod čiarou.";
        $frontMatter = [
            'schemaVersion' => 2,
            'defaultLocale' => 'sk',
            'localizedContent' => [
                'sk' => ['title' => 'Beta', 'body' => $body],
            ],
            'localeStatus' => ['sk' => 'draft'],
            'slug' => 'beta',
        ];

        $file = $this->parser->serialize($frontMatter) . $body;

        $parsedFm = $this->parser->parse($file);
        $this->assertSame('beta', $parsedFm['slug']);
        $this->assertSame('draft', $parsedFm['localeStatus']['sk']);
        $this->assertSame($body, $parsedFm['localizedContent']['sk']['body']);

        $this->assertSame($body, $this->parser->extractContent($file));
        $this->assertStringNotContainsString('localeStatus:', $this->parser->extractContent($file));
    }

    public function testEmptyFrontMatterBlockParses(): void
    {
        $content = "---\n---\n# Content";
        $this->assertTrue($this->parser->hasFrontMatter($content));
        $this->assertSame('# Content', $this->parser->extractContent($content));
    }
}
