<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Media\Services;

use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;
use PaginiumCMS\Modules\Media\Contracts\MediaStorageDriverInterface;
use PaginiumCMS\Modules\Media\Services\MediaUrlResolver;
use PaginiumCMS\Modules\Media\Services\S3MediaStorageConfig;
use PaginiumCMS\Modules\Media\Services\S3MediaStorageDriver;
use PHPUnit\Framework\TestCase;

final class S3MediaStorageDriverTest extends TestCase
{
    use MediaStorageDriverContractTestTrait;

    protected function createDriver(): MediaStorageDriverInterface
    {
        return $this->buildDriver('private', '');
    }

    public function testPublicUrlUsesApiPathForPrivateBucket(): void
    {
        $driver = $this->buildDriver('private', '');

        $this->assertSame(
            '/api/media/file/media%2Fphoto.png',
            $driver->publicUrl('media/photo.png')
        );
    }

    public function testPublicUrlUsesCdnBaseWhenConfigured(): void
    {
        $driver = $this->buildDriver('public', 'https://cdn.example.com/assets');

        $this->assertSame(
            'https://cdn.example.com/assets/media/photo.png',
            $driver->publicUrl('media/photo.png')
        );
    }

    private function buildDriver(string $visibility, string $publicBaseUrl): S3MediaStorageDriver
    {
        $config = S3MediaStorageConfig::fromSettings([
            's3Bucket' => 'media-bucket',
            's3Region' => 'eu-central-1',
            's3KeyId' => 'AKIAEXAMPLE',
            's3Secret' => 'secret',
            's3Endpoint' => '',
            's3PathStyle' => false,
            's3PublicBaseUrl' => $publicBaseUrl,
            's3Visibility' => $visibility,
        ], new OutboundUrlGuard(true, true));

        return new S3MediaStorageDriver(
            new Filesystem(new InMemoryFilesystemAdapter()),
            $config,
            new MediaUrlResolver(),
        );
    }
}
