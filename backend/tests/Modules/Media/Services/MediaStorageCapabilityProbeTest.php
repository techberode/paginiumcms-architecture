<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Media\Services;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Modules\Media\Services\LocalMediaStorageDriver;
use PaginiumCMS\Modules\Media\Services\MediaStorageCapabilityProbe;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

final class MediaStorageCapabilityProbeTest extends TestCase
{
    public function testProbeReportsLocalActiveAndS3Unavailable(): void
    {
        vfsStream::setup('storage', null, ['content' => []]);
        $root = vfsStream::url('storage/content');
        $validator = new FileValidator($root);
        $driver = new LocalMediaStorageDriver(new FileReader($validator), new FileWriter($validator));
        $probe = new MediaStorageCapabilityProbe();

        $result = $probe->probe($driver, ['storageDriver' => 'local']);

        $this->assertSame('local', $result['storageDriver']['configured']);
        $this->assertSame('local', $result['storageDriver']['active']);
        $this->assertSame('active', $result['storageDriver']['status']);
        $this->assertSame('available', $result['capabilities']['localStorage']['status']);
        $this->assertSame('unavailable', $result['capabilities']['s3Storage']['status']);
        $this->assertTrue($result['health']['ok']);
    }

    public function testProbeReportsFallbackWhenS3Configured(): void
    {
        vfsStream::setup('storage2', null, ['content' => []]);
        $root = vfsStream::url('storage2/content');
        $validator = new FileValidator($root);
        $driver = new LocalMediaStorageDriver(new FileReader($validator), new FileWriter($validator));
        $probe = new MediaStorageCapabilityProbe();

        $result = $probe->probe($driver, ['storageDriver' => 's3']);

        $this->assertSame('s3', $result['storageDriver']['configured']);
        $this->assertSame('local', $result['storageDriver']['active']);
        $this->assertSame('fallback', $result['storageDriver']['status']);
        $this->assertSame('unavailable', $result['capabilities']['s3Storage']['status']);
    }
}
