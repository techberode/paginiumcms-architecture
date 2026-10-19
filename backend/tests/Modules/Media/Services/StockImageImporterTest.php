<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Media\Services;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;
use PaginiumCMS\Core\Security\Services\UploadSecurityValidator;
use PaginiumCMS\Tests\Support\UploadPolicyEngineTestFactory;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Media\Services\MediaImageOptimizer;
use PaginiumCMS\Modules\Media\Services\MediaOptimizePreviewStore;
use PaginiumCMS\Modules\Media\Services\MediaRepository;
use PaginiumCMS\Modules\Media\Services\MediaStorageFactory;
use PaginiumCMS\Modules\Media\Services\MediaUrlResolver;
use PaginiumCMS\Modules\Media\Services\S3MediaFilesystemFactory;
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
        $storageFactory = new MediaStorageFactory(
            $reader,
            $writer,
            $settings,
            new OutboundUrlGuard(true, true),
            new S3MediaFilesystemFactory(),
            new MediaUrlResolver(),
        );
        $repository = new MediaRepository(
            $reader,
            $writer,
            $settings,
            $uploadSecurity,
            $policyEngine,
            $storageFactory,
            new MediaImageOptimizer(),
            new MediaOptimizePreviewStore($reader, $writer),
            new \PaginiumCMS\Core\Security\Upload\PolyglotUploadGuard($settings)
        );
        $importer = new StockImageImporter($repository, $settings, $catalog, $policyEngine, new OutboundUrlGuard(true, true));

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
        $importer = new StockImageImporter($mediaRepo, $settings, $catalog, $policyEngine, new OutboundUrlGuard(true, true));

        $this->expectException(\PaginiumCMS\Core\FlatFile\Exception\FlatFileException::class);
        $importer->import('food');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function rejectedDownloadUrls(): iterable
    {
        yield 'foreign host' => ['https://evil.example/a.jpg'];
        yield 'metadata ip' => ['https://169.254.169.254/latest/meta-data'];
        yield 'plain http' => ['http://images.unsplash.com/photo.jpg'];
        yield 'lookalike host' => ['https://images.unsplash.com.evil.example/a.jpg'];
        yield 'userinfo' => ['https://user@images.unsplash.com/a.jpg'];
    }

    /**
     * @dataProvider rejectedDownloadUrls
     */
    public function testDownloadUrlIsRejected(string $url): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $importer = new StockImageImporter(
            $this->createMock(\PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface::class),
            $settings,
            new StockImageCatalog(__DIR__ . '/Fixtures/stock-images-test.json'),
            UploadPolicyEngineTestFactory::create($settings),
            new OutboundUrlGuard(false, false),
        );

        $this->expectException(\PaginiumCMS\Core\FlatFile\Exception\FlatFileException::class);
        $importer->assertDownloadableUrl($url);
    }
}
