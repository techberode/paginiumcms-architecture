<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Media\Services;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Security\Services\UploadSecurityValidator;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Media\Services\MediaRepository;
use PaginiumCMS\Modules\Media\Services\StockImageCatalog;
use PaginiumCMS\Modules\Media\Services\StockImageImporter;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

class StockImageImporterTest extends TestCase
{
    public function testImportUsesTopicFromSettings(): void
    {
        vfsStream::setup('storage', null, ['content' => []]);
        $root = vfsStream::url('storage/content');

        $validator = new FileValidator($root);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);

        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnCallback(function (string $group): array {
            if ($group === 'media') {
                return [
                    'allowedMimeTypes' => 'image/jpeg,image/png,image/gif,image/webp',
                    'maxUploadSizeKb' => 5120,
                    'stockImagesEnabled' => true,
                    'stockImageTopic' => 'food',
                ];
            }

            return [];
        });

        $catalog = new StockImageCatalog(__DIR__ . '/Fixtures/stock-images-test.json');
        $uploadSecurity = new UploadSecurityValidator($settings);
        $repository = new MediaRepository($reader, $writer, $settings, $uploadSecurity);
        $importer = new StockImageImporter($repository, $settings, $catalog);

        $media = $importer->import('', 'stock');

        $this->assertSame('food-sample.png', $media->getFileName());
        $this->assertSame('Food alt', $media->getAltText());
        $this->assertSame('Food title', $media->getTitle());
        $this->assertSame('stock', $media->getFolder());
        $this->assertStringStartsWith('media/stock/', $media->getPath());
    }

    public function testImportDisabledInSettingsThrows(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->with('media')->willReturn([
            'stockImagesEnabled' => false,
            'stockImageTopic' => 'general',
        ]);

        $catalog = new StockImageCatalog(__DIR__ . '/Fixtures/stock-images-test.json');
        $mediaRepo = $this->createMock(\PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface::class);
        $importer = new StockImageImporter($mediaRepo, $settings, $catalog);

        $this->expectException(\PaginiumCMS\Core\FlatFile\Exception\FlatFileException::class);
        $importer->import('food');
    }
}
