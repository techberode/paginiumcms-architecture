<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Media\Services;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Media\Services\MediaRepository;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

class MediaRepositoryTest extends TestCase
{
    private const PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private MediaRepository $repository;
    private string $root;

    private function pngBytes(): string
    {
        $bytes = base64_decode(self::PNG_BASE64, true);
        $this->assertNotFalse($bytes);

        return $bytes;
    }

    private function minimalPdfBytes(): string
    {
        return "%PDF-1.4\n%%EOF\n";
    }

    protected function setUp(): void
    {
        $root = vfsStream::setup('storage', null, [
            'content' => [],
        ]);
        $this->root = vfsStream::url('storage/content');

        $validator = new FileValidator($this->root);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);

        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->with('media')->willReturn([
            'allowedMimeTypes' => 'image/jpeg,image/png,image/gif,image/webp,image/svg+xml,application/pdf',
            'maxUploadSizeKb' => 5120,
        ]);

        $this->repository = new MediaRepository($reader, $writer, $settings);
    }

    public function testFindAllReturnsEmptyWhenRegistryMissing(): void
    {
        $this->assertSame([], $this->repository->findAll());
    }

    public function testSaveUploadCreatesRegistryAndFile(): void
    {
        $pngBytes = $this->pngBytes();

        $media = $this->repository->saveUpload('photo.png', $pngBytes, 'image/png', 'Test alt');

        $this->assertSame('photo.png', $media->getFileName());
        $this->assertSame('image/png', $media->getMimeType());
        $this->assertSame('Test alt', $media->getAltText());
        $this->assertSame('', $media->getFolder());
        $this->assertTrue($media->isImage());
        $this->assertStringStartsWith('media/', $media->getPath());

        $all = $this->repository->findAll();
        $this->assertCount(1, $all);
        $this->assertSame($media->getId(), $all[0]->getId());
    }

    public function testSaveUploadPreservesBinaryPngHeader(): void
    {
        $pngBytes = $this->pngBytes();
        $media = $this->repository->saveUpload('binary.png', $pngBytes, 'image/png');

        $stored = file_get_contents($this->root . '/' . $media->getPath());
        $this->assertSame($pngBytes, $stored);
        $this->assertSame("\x89PNG\r\n\x1a\n", substr($stored, 0, 8));
    }

    public function testSaveUploadIntoFolder(): void
    {
        $this->repository->createFolder('campaigns');

        $media = $this->repository->saveUpload('hero.png', $this->pngBytes(), 'image/png', '', 'campaigns');

        $this->assertSame('campaigns', $media->getFolder());
        $this->assertStringStartsWith('media/campaigns/', $media->getPath());

        $filtered = $this->repository->findAll(['folder' => 'campaigns']);
        $this->assertCount(1, $filtered);
        $this->assertSame($media->getId(), $filtered[0]->getId());
    }

    public function testFindByPathReturnsSavedMedia(): void
    {
        $media = $this->repository->saveUpload('doc.pdf', $this->minimalPdfBytes(), 'application/pdf');

        $found = $this->repository->findByPath($media->getPath());
        $this->assertNotNull($found);
        $this->assertSame('doc.pdf', $found->getFileName());
        $this->assertFalse($found->isImage());
    }

    public function testFindAllFiltersImagesOnly(): void
    {
        $this->repository->saveUpload('a.png', $this->pngBytes(), 'image/png');
        $this->repository->saveUpload('b.pdf', $this->minimalPdfBytes(), 'application/pdf');

        $images = $this->repository->findAll(['type' => 'image']);
        $this->assertCount(1, $images);
        $this->assertTrue($images[0]->isImage());
    }

    public function testUpdateMetadataWritesSidecar(): void
    {
        $media = $this->repository->saveUpload('icon.png', $this->pngBytes(), 'image/png');
        $media->setAltText('Updated alt');
        $media->setTitle('Updated title');

        $this->repository->update($media);

        $found = $this->repository->findByPath($media->getPath());
        $this->assertNotNull($found);
        $this->assertSame('Updated alt', $found->getAltText());
        $this->assertSame('Updated title', $found->getTitle());
    }

    public function testDeleteRemovesFileRegistryAndSidecar(): void
    {
        $media = $this->repository->saveUpload('remove.png', $this->pngBytes(), 'image/png');
        $path = $media->getPath();

        $this->repository->delete($path);

        $this->assertNull($this->repository->findByPath($path));
        $this->assertSame([], $this->repository->findAll());
    }

    public function testBulkDeleteRemovesMultipleFiles(): void
    {
        $first = $this->repository->saveUpload('one.png', $this->pngBytes(), 'image/png');
        $second = $this->repository->saveUpload('two.png', $this->pngBytes(), 'image/png');

        $deleted = $this->repository->bulkDelete([$first->getPath(), $second->getPath(), 'media/missing.png']);

        $this->assertSame(2, $deleted);
        $this->assertSame([], $this->repository->findAll());
    }

    public function testListFoldersIncludesRootAndCreatedFolder(): void
    {
        $this->repository->createFolder('assets/icons');
        $this->repository->saveUpload('logo.png', $this->pngBytes(), 'image/png', '', 'assets/icons');

        $folders = $this->repository->listFolders();

        $this->assertContains('', $folders);
        $this->assertContains('assets/icons', $folders);
    }

    public function testDeleteMissingThrows(): void
    {
        $this->expectException(FlatFileException::class);
        $this->repository->delete('media/missing.png');
    }

    public function testSaveUploadRejectsInvalidContent(): void
    {
        $this->expectException(FlatFileException::class);
        $this->repository->saveUpload('photo.png', 'not-a-png', 'image/png');
    }

    public function testSaveUploadRejectsUnsupportedMimeType(): void
    {
        $this->expectException(FlatFileException::class);
        $this->repository->saveUpload('notes.txt', 'hello', 'text/plain');
    }

    public function testCreateFolderRejectsInvalidName(): void
    {
        $this->expectException(FlatFileException::class);
        $this->repository->createFolder('../escape');
    }
}
