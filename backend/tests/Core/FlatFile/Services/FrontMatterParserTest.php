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
}
