<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\FlatFile\Services;

use PaginiumCMS\Core\FlatFile\Services\MarkdownContentParser;
use PHPUnit\Framework\TestCase;

class MarkdownContentParserTest extends TestCase
{
    private MarkdownContentParser $parser;

    protected function setUp(): void
    {
        $this->parser = new MarkdownContentParser();
    }

    public function testParseBasicMarkdown(): void
    {
        $markdown = '# Heading 1';
        $html = $this->parser->parse($markdown);
        $this->assertStringContainsString('<h1>Heading 1</h1>', $html);
    }

    public function testParseBoldText(): void
    {
        $markdown = 'This is **bold** text';
        $html = $this->parser->parse($markdown);
        $this->assertStringContainsString('<strong>bold</strong>', $html);
    }

    public function testParseItalicText(): void
    {
        $markdown = 'This is *italic* text';
        $html = $this->parser->parse($markdown);
        $this->assertStringContainsString('<em>italic</em>', $html);
    }

    public function testParseUnorderedList(): void
    {
        $markdown = "- Item 1\n- Item 2\n- Item 3";
        $html = $this->parser->parse($markdown);
        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('<li>Item 1</li>', $html);
    }

    public function testParseOrderedList(): void
    {
        $markdown = "1. First\n2. Second\n3. Third";
        $html = $this->parser->parse($markdown);
        $this->assertStringContainsString('<ol>', $html);
        $this->assertStringContainsString('<li>First</li>', $html);
    }

    public function testParseLinks(): void
    {
        $markdown = '[Google](https://google.com)';
        $html = $this->parser->parse($markdown);

        // CommonMark pridáva rel="nofollow noopener noreferrer" a ďalšie atribúty
        $this->assertStringContainsString('href="https://google.com"', $html);
        $this->assertStringContainsString('Google', $html);
        $this->assertStringContainsString('external-link', $html);
    }

    public function testParseImages(): void
    {
        $markdown = '![Alt text](https://example.com/image.jpg)';
        $html = $this->parser->parse($markdown);
        $this->assertStringContainsString('<img src="https://example.com/image.jpg" alt="Alt text"', $html);
    }

    public function testParseCodeBlock(): void
    {
        $markdown = "```php\n<?php echo 'Hello';\n```";
        $html = $this->parser->parse($markdown);

        // CommonMark používa <pre><code class="language-php">
        $this->assertStringContainsString('<pre>', $html);
        $this->assertStringContainsString('<code', $html);
        $this->assertStringContainsString('Hello', $html);
        // Kontrola, či je tam trieda jazyka (môže byť language-php alebo php)
        $this->assertMatchesRegularExpression('/class="[^"]*language-php[^"]*"/', $html);
    }

    public function testStripMarkdown(): void
    {
        $markdown = "# Heading\n**bold** and *italic*";
        $text = $this->parser->stripMarkdown($markdown);
        $this->assertEquals("Heading\nbold and italic", $text);
    }

    public function testExtractExcerpt(): void
    {
        $markdown = "# Heading\nThis is a longer text that should be truncated to a specific length.";
        $excerpt = $this->parser->extractExcerpt($markdown, 20);
        $this->assertStringEndsWith('…', $excerpt);
        $this->assertLessThanOrEqual(20, strlen($excerpt));
    }

    public function testParseInline(): void
    {
        $markdown = 'This is **inline** text';
        $html = $this->parser->parseInline($markdown);
        $this->assertStringContainsString('<strong>inline</strong>', $html);
    }

    public function testParseTable(): void
    {
        $markdown = "| Header 1 | Header 2 |\n|----------|----------|\n| Cell 1   | Cell 2   |";
        $html = $this->parser->parse($markdown);
        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('<th>Header 1</th>', $html);
        $this->assertStringContainsString('<td>Cell 1</td>', $html);
    }

    public function testParseTaskList(): void
    {
        $markdown = "- [x] Completed\n- [ ] Pending";
        $html = $this->parser->parse($markdown);
        $this->assertStringContainsString('checked', $html);
        $this->assertStringContainsString('Pending', $html);
    }
}
