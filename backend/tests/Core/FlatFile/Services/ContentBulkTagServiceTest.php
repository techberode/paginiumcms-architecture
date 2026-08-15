<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\FlatFile\Services;

use PaginiumCMS\Core\FlatFile\Models\Page;
use PaginiumCMS\Core\FlatFile\Services\ContentBulkTagService;
use PaginiumCMS\Core\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

final class ContentBulkTagServiceTest extends TestCase
{
    private ContentBulkTagService $service;

    protected function setUp(): void
    {
        $this->service = new ContentBulkTagService();
    }

    public function testAddMergesUniqueTags(): void
    {
        $page = new Page();
        $page->setTags(['news', 'php']);

        $this->service->apply($page, 'add', ['php', 'cms']);

        $this->assertSame(['news', 'php', 'cms'], $page->getTags());
    }

    public function testRemoveDeletesSelectedTags(): void
    {
        $page = new Page();
        $page->setTags(['news', 'php', 'cms']);

        $this->service->apply($page, 'remove', ['php', 'missing']);

        $this->assertSame(['news', 'cms'], $page->getTags());
    }

    public function testReplaceSetsExactTagList(): void
    {
        $page = new Page();
        $page->setTags(['news', 'php']);

        $this->service->apply($page, 'replace', ['archived']);

        $this->assertSame(['archived'], $page->getTags());
    }

    public function testAddRequiresNonEmptyTags(): void
    {
        $page = new Page();
        $page->setTags(['news']);

        $this->expectException(ValidationException::class);
        $this->service->apply($page, 'add', []);
    }

    public function testRejectInvalidMode(): void
    {
        $page = new Page();
        $page->setTags(['news']);

        $this->expectException(ValidationException::class);
        $this->service->apply($page, 'merge', ['cms']);
    }
}
