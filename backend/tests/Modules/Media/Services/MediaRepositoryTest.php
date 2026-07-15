<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Media\Services;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Modules\Media\Services\MediaRepository;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

class MediaRepositoryTest extends TestCase
{
    private MediaRepository $repository;
    private string $root;

    protected function setUp(): void
    {
        $root = vfsStream::setup('storage', null, [
            'content' => [],
        ]);
        $this->root = vfsStream::url('storage/content');

        $validator = new FileValidator($this->root);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);

        $this->repository = new MediaRepository($reader, $writer);
    }

    public function testFindAllReturnsEmptyWhenRegistryMissing(): void
    {
        $this->assertSame([], $this->repository->findAll());
    }

    public function testSaveUploadCreatesRegistryAndFile(): void
    {
        $pngBytes = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true
        );
        $this->assertNotFalse($pngBytes);

        $media = $this->repository->saveUpload('photo.png', $pngBytes, 'image/png', 'Test alt');

        $this->assertSame('photo.png', $media->getFileName());
        $this->assertSame('image/png', $media->getMimeType());
        $this->assertSame('Test alt', $media->getAltText());
        $this->assertTrue($media->isImage());
        $this->assertStringStartsWith('media/', $media->getPath());

        $all = $this->repository->findAll();
        $this->assertCount(1, $all);
        $this->assertSame($media->getId(), $all[0]->getId());
    }

    public function testFindByPathReturnsSavedMedia(): void
    {
        $media = $this->repository->saveUpload('doc.pdf', '%PDF-1.4', 'application/pdf');

        $found = $this->repository->findByPath($media->getPath());
        $this->assertNotNull($found);
        $this->assertSame('doc.pdf', $found->getFileName());
        $this->assertFalse($found->isImage());
    }

    public function testFindAllFiltersImagesOnly(): void
    {
        $this->repository->saveUpload('a.png', 'png', 'image/png');
        $this->repository->saveUpload('b.pdf', '%PDF', 'application/pdf');

        $images = $this->repository->findAll(['type' => 'image']);
        $this->assertCount(1, $images);
        $this->assertTrue($images[0]->isImage());
    }

    public function testUpdateAltText(): void
    {
        $media = $this->repository->saveUpload('icon.png', 'png-bytes', 'image/png');
        $media->setAltText('Updated alt');

        $this->repository->update($media);

        $found = $this->repository->findByPath($media->getPath());
        $this->assertNotNull($found);
        $this->assertSame('Updated alt', $found->getAltText());
    }

    public function testDeleteRemovesFileAndRegistryEntry(): void
    {
        $media = $this->repository->saveUpload('remove.png', 'png-bytes', 'image/png');
        $path = $media->getPath();

        $this->repository->delete($path);

        $this->assertNull($this->repository->findByPath($path));
        $this->assertSame([], $this->repository->findAll());
    }

    public function testDeleteMissingThrows(): void
    {
        $this->expectException(FlatFileException::class);
        $this->repository->delete('media/missing.png');
    }

    public function testSaveUploadRejectsUnsupportedMimeType(): void
    {
        $this->expectException(FlatFileException::class);
        $this->repository->saveUpload('notes.txt', 'hello', 'text/plain');
    }
}
