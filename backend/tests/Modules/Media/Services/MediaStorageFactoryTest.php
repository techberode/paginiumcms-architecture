<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Media\Services;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Media\Exception\UnknownMediaStorageDriverException;
use PaginiumCMS\Modules\Media\Services\LocalMediaStorageDriver;
use PaginiumCMS\Modules\Media\Services\MediaStorageFactory;
use PaginiumCMS\Modules\Media\Services\MediaUrlResolver;
use PaginiumCMS\Modules\Media\Services\S3MediaFilesystemFactory;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

final class MediaStorageFactoryTest extends TestCase
{
    private MediaStorageFactory $factory;

    protected function setUp(): void
    {
        vfsStream::setup('storage', null, ['content' => []]);
        $root = vfsStream::url('storage/content');
        $validator = new FileValidator($root);
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->with('media')->willReturn(['storageDriver' => 'local']);

        $this->factory = new MediaStorageFactory(
            new FileReader($validator),
            new FileWriter($validator),
            $settings,
            new OutboundUrlGuard(true, true),
            new S3MediaFilesystemFactory(),
            new MediaUrlResolver(),
        );
    }

    public function testDefaultCreatesLocalDriver(): void
    {
        $driver = $this->factory->create(null);

        $this->assertInstanceOf(LocalMediaStorageDriver::class, $driver);
        $this->assertTrue($driver->health()['ok']);
    }

    public function testLocalDriverExplicit(): void
    {
        $driver = $this->factory->create('local');

        $this->assertInstanceOf(LocalMediaStorageDriver::class, $driver);
    }

    public function testIncompleteS3FallsBackToLocal(): void
    {
        $settings = ['storageDriver' => 's3', 's3Bucket' => 'bucket'];

        $this->assertSame('local', MediaStorageFactory::driverFromMediaSettings($settings, new OutboundUrlGuard(true, true)));

        $driver = $this->factory->create('s3', true, $settings);
        $this->assertInstanceOf(LocalMediaStorageDriver::class, $driver);
    }

    public function testCompleteS3ConfigNormalizesToS3(): void
    {
        $settings = [
            'storageDriver' => 's3',
            's3Bucket' => 'media-bucket',
            's3Region' => 'eu-central-1',
            's3KeyId' => 'AKIAEXAMPLE',
            's3Secret' => 'secret',
        ];

        $this->assertSame('s3', MediaStorageFactory::driverFromMediaSettings($settings, new OutboundUrlGuard(true, true)));
    }

    public function testUnknownDriverFallsBackToLocalWithAllowFallback(): void
    {
        $driver = $this->factory->create('ftp');

        $this->assertInstanceOf(LocalMediaStorageDriver::class, $driver);
    }

    public function testUnknownDriverWithoutFallbackThrows(): void
    {
        $this->expectException(UnknownMediaStorageDriverException::class);
        $this->factory->create('ftp', false);
    }

    public function testIncompleteS3WithoutFallbackThrows(): void
    {
        $this->expectException(UnknownMediaStorageDriverException::class);
        $this->factory->create('s3', false, ['storageDriver' => 's3']);
    }
}
