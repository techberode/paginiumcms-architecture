<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\FlatFile\Models;

use PaginiumCMS\Core\FlatFile\Models\Content;
use PHPUnit\Framework\TestCase;

/**
 * Test pre abstraktný model Content.
 */
class ContentTest extends TestCase
{
    private Content $content;

    protected function setUp(): void
    {
        // Vytvoríme anonymnú triedu pre testovanie abstraktnej triedy
        $this->content = new class() extends Content {};
    }

    public function testSetAndGetPath(): void
    {
        $this->content->setPath('pages/test.md');
        $this->assertEquals('pages/test.md', $this->content->getPath());
    }

    public function testSetAndGetFrontMatter(): void
    {
        $frontMatter = ['title' => 'Test', 'slug' => 'test'];
        $this->content->setFrontMatter($frontMatter);
        $this->assertEquals($frontMatter, $this->content->getFrontMatter());
    }

    public function testSetAndGetContent(): void
    {
        $content = '# Test Content';
        $this->content->setContent($content);
        $this->assertEquals($content, $this->content->getContent());
    }

    public function testSetAndGetHtml(): void
    {
        $html = '<h1>Test Content</h1>';
        $this->content->setHtml($html);
        $this->assertEquals($html, $this->content->getHtml());
    }

    public function testGetTitleFromFrontMatter(): void
    {
        $this->content->setFrontMatter(['title' => 'My Title']);
        $this->assertEquals('My Title', $this->content->getTitle());
    }

    public function testGetTitleReturnsEmptyStringIfNotSet(): void
    {
        $this->content->setFrontMatter([]);
        $this->assertEquals('', $this->content->getTitle());
    }

    public function testGetSlugFromFrontMatter(): void
    {
        $this->content->setFrontMatter(['slug' => 'my-slug']);
        $this->assertEquals('my-slug', $this->content->getSlug());
    }

    public function testGetStatusReturnsDraftAsDefault(): void
    {
        $this->content->setFrontMatter([]);
        $this->assertEquals('draft', $this->content->getStatus());
    }

    public function testIsPublished(): void
    {
        $this->content->setFrontMatter(['status' => 'published']);
        $this->assertTrue($this->content->isPublished());
        $this->assertFalse($this->content->isDraft());
        $this->assertFalse($this->content->isArchived());
    }

    public function testIsDraft(): void
    {
        $this->content->setFrontMatter(['status' => 'draft']);
        $this->assertTrue($this->content->isDraft());
        $this->assertFalse($this->content->isPublished());
        $this->assertFalse($this->content->isArchived());
    }

    public function testIsArchived(): void
    {
        $this->content->setFrontMatter(['status' => 'archived']);
        $this->assertTrue($this->content->isArchived());
        $this->assertFalse($this->content->isPublished());
        $this->assertFalse($this->content->isDraft());
    }

    public function testJsonSerialize(): void
    {
        $this->content->setPath('pages/test.md');
        $this->content->setFrontMatter(['title' => 'Test']);
        $this->content->setContent('# Content');
        $this->content->setHtml('<h1>Content</h1>');
        $this->content->setSize(1024);
        $this->content->setModifiedAt(1234567890);

        $data = $this->content->jsonSerialize();

        $this->assertArrayHasKey('path', $data);
        $this->assertArrayHasKey('frontMatter', $data);
        $this->assertArrayHasKey('content', $data);
        $this->assertArrayHasKey('html', $data);
        $this->assertArrayHasKey('size', $data);
        $this->assertArrayHasKey('modifiedAt', $data);
    }
}
