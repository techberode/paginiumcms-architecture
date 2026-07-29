<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Gallery\Services;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Modules\Gallery\Models\GalleryItem;
use PaginiumCMS\Modules\Gallery\Services\GalleryRepository;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

final class GalleryRepositoryTest extends TestCase
{
    private GalleryRepository $repository;

    protected function setUp(): void
    {
        vfsStream::setup('storage', null, ['content' => []]);
        $root = vfsStream::url('storage/content');

        $validator = new FileValidator($root);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);

        $this->repository = new GalleryRepository($reader, $writer);
    }

    public function testCreateUpdatePublishReorderDelete(): void
    {
        $item = $this->repository->create([
            'title' => 'Analytics dashboard',
            'description' => 'Traffic overview',
            'mediaPath' => '/storage/media/analytics.png',
            'featureTag' => 'analytics',
            'status' => GalleryItem::STATUS_DRAFT,
        ]);

        $this->assertSame('Analytics dashboard', $item->getTitle());
        $this->assertFalse($item->isPublished());

        $published = $this->repository->update($item->getId(), [
            'status' => GalleryItem::STATUS_PUBLISHED,
        ]);
        $this->assertTrue($published->isPublished());
        $this->assertNotNull($published->getPublishedAt());

        $second = $this->repository->create([
            'title' => 'Newsletter',
            'mediaPath' => '/storage/media/newsletter.png',
            'status' => GalleryItem::STATUS_PUBLISHED,
        ]);

        $this->repository->reorder([$second->getId(), $item->getId()]);
        $ordered = $this->repository->findAllOrdered();
        $this->assertSame($second->getId(), $ordered[0]->getId());

        $publishedOnly = $this->repository->findPublishedOrdered();
        $this->assertCount(2, $publishedOnly);

        $this->repository->delete($item->getId());
        $this->assertNull($this->repository->findById($item->getId()));
        $this->assertCount(1, $this->repository->findAllOrdered());
    }

    public function testDeleteMissingThrows(): void
    {
        $this->expectException(FlatFileException::class);
        $this->repository->delete('missing');
    }
}
