<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Media\Services;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Security\Services\UploadSecurityValidator;
use PaginiumCMS\Tests\Support\UploadPolicyEngineTestFactory;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Media\Services\MediaImageOptimizer;
use PaginiumCMS\Modules\Media\Services\MediaOptimizePreviewStore;
use PaginiumCMS\Modules\Media\Services\MediaRepository;
use PaginiumCMS\Modules\Media\Services\MediaStorageFactory;
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

            if ($group === 'uploadSecurity') {
                return ['unifiedPolicyEnabled' => false];
            }

            return [];
        });

        $catalog = new StockImageCatalog(__DIR__ . '/Fixtures/stock-images-test.json');
        $policyEngine = UploadPolicyEngineTestFactory::create($settings, $root);
        $uploadSecurity = new UploadSecurityValidator($settings, $policyEngine);
        $storageFactory = new MediaStorageFactory($reader, $writer);
        $repository = new MediaRepository(
            $reader,
            $writer,
            $settings,
            $uploadSecurity,
            $policyEngine,
            $storageFactory,
            new MediaImageOptimizer(),
            new MediaOptimizePreviewStore($reader, $writer)
        );
        $importer = new StockImageImporter($repository, $settings, $catalog, $policyEngine);

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
        $settings->method('group')->willReturnCallback(function (string $group): array {
            if ($group === 'media') {
                return [
                    'stockImagesEnabled' => false,
                    'stockImageTopic' => 'general',
                ];
            }

            if ($group === 'uploadSecurity') {
                return ['unifiedPolicyEnabled' => false];
            }

            return [];
        });

        $catalog = new StockImageCatalog(__DIR__ . '/Fixtures/stock-images-test.json');
        $mediaRepo = $this->createMock(\PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface::class);
        $policyEngine = UploadPolicyEngineTestFactory::create($settings);
        $importer = new StockImageImporter($mediaRepo, $settings, $catalog, $policyEngine);

        $this->expectException(\PaginiumCMS\Core\FlatFile\Exception\FlatFileException::class);
        $importer->import('food');
    }
}
