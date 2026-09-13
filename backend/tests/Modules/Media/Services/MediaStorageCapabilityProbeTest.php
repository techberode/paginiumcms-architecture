<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Media\Services;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Media\Services\LocalMediaStorageDriver;
use PaginiumCMS\Modules\Media\Services\MediaStorageCapabilityProbe;
use PaginiumCMS\Modules\Media\Services\MediaStorageFactory;
use PaginiumCMS\Modules\Media\Services\MediaUrlResolver;
use PaginiumCMS\Modules\Media\Services\S3MediaFilesystemFactory;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

final class MediaStorageCapabilityProbeTest extends TestCase
{
    private function createProbe(): MediaStorageCapabilityProbe
    {
        vfsStream::setup('storage', null, ['content' => []]);
        $root = vfsStream::url('storage/content');
        $validator = new FileValidator($root);
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->with('media')->willReturn(['storageDriver' => 'local']);

        $factory = new MediaStorageFactory(
            new FileReader($validator),
            new FileWriter($validator),
            $settings,
            new OutboundUrlGuard(true, true),
            new S3MediaFilesystemFactory(),
            new MediaUrlResolver(),
        );

        return new MediaStorageCapabilityProbe($factory, new OutboundUrlGuard(true, true));
    }

    public function testProbeReportsLocalActiveAndS3Unavailable(): void
    {
        vfsStream::setup('storage', null, ['content' => []]);
        $root = vfsStream::url('storage/content');
        $validator = new FileValidator($root);
        $driver = new LocalMediaStorageDriver(new FileReader($validator), new FileWriter($validator), new MediaUrlResolver());
        $probe = $this->createProbe();

        $result = $probe->probe($driver, ['storageDriver' => 'local']);

        $this->assertSame('local', $result['storageDriver']['configured']);
        $this->assertSame('local', $result['storageDriver']['active']);
        $this->assertSame('active', $result['storageDriver']['status']);
        $this->assertSame('available', $result['capabilities']['localStorage']['status']);
        $this->assertSame('unavailable', $result['capabilities']['s3Storage']['status']);
        $this->assertTrue($result['health']['ok']);
    }

    public function testProbeReportsFallbackWhenS3Incomplete(): void
    {
        vfsStream::setup('storage2', null, ['content' => []]);
        $root = vfsStream::url('storage2/content');
        $validator = new FileValidator($root);
        $driver = new LocalMediaStorageDriver(new FileReader($validator), new FileWriter($validator), new MediaUrlResolver());
        $probe = $this->createProbe();

        $result = $probe->probe($driver, [
            'storageDriver' => 's3',
            's3Region' => 'eu-central-1',
            's3Bucket' => 'media-bucket',
            's3KeyId' => 'AKIAEXAMPLE',
            's3Secret' => '',
        ]);

        $this->assertSame('s3', $result['storageDriver']['configured']);
        $this->assertSame('local', $result['storageDriver']['active']);
        $this->assertSame('fallback', $result['storageDriver']['status']);
        $this->assertSame('unavailable', $result['capabilities']['s3Storage']['status']);
    }
}
