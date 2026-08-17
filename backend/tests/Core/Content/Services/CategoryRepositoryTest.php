<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Content\Services;

use PaginiumCMS\Core\Content\Models\CategoryRecord;
use PaginiumCMS\Core\Content\Services\CategoryRepository;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

final class CategoryRepositoryTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        vfsStream::setup('storage');
        $this->root = vfsStream::url('storage');
    }

    public function testSaveListAndDeleteCategory(): void
    {
        $repository = $this->repository();

        $record = $repository->save('news', 'News');
        $this->assertSame('news', $record->slug);
        $this->assertSame('News', $record->label);

        $items = $repository->list();
        $this->assertCount(1, $items);
        $this->assertSame(['slug' => 'news', 'label' => 'News'], $items[0]);

        $repository->save('news', 'News & Updates');
        $this->assertSame('News & Updates', $repository->get('news')?->label);

        $repository->delete('news');
        $this->assertSame([], $repository->list());
    }

    public function testSummarizeForSlugsUsesRegistryLabels(): void
    {
        $repository = $this->repository();
        $repository->save('security', 'Security');

        $summary = $repository->summarizeForSlugs(['security', 'unknown-topic']);
        $this->assertSame([
            ['slug' => 'security', 'label' => 'Security'],
            ['slug' => 'unknown-topic', 'label' => 'Unknown Topic'],
        ], $summary);
    }

    public function testRejectsInvalidSlug(): void
    {
        $repository = $this->repository();

        $this->expectException(\RuntimeException::class);
        $repository->save(CategoryRecord::normalizeSlug('Bad Slug!'), 'Bad');
    }

    private function repository(): CategoryRepository
    {
        $validator = new FileValidator($this->root);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);

        return new CategoryRepository($reader, $writer);
    }
}
