<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Storage;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Storage\Exception\UnknownStorageDriverException;
use PaginiumCMS\Core\Storage\StorageFactory;
use PHPUnit\Framework\TestCase;

final class StorageFactoryTest extends TestCase
{
    private StorageFactory $factory;

    protected function setUp(): void
    {
        $base = sys_get_temp_dir() . '/pag_storage_factory_' . uniqid('', true);
        mkdir($base, 0777, true);
        $validator = new FileValidator($base);
        $this->factory = new StorageFactory(new FileReader($validator), new FileWriter($validator), $validator);
    }

    public function testDefaultDriverIsLocal(): void
    {
        $storage = $this->factory->create(null, true);
        $this->assertSame($storage->getBasePath(), $storage->getBasePath());
        $this->assertNotSame('', $storage->getBasePath());
    }

    public function testUnknownDriverThrowsInStrictMode(): void
    {
        $this->expectException(UnknownStorageDriverException::class);
        $this->factory->create('redis', false);
    }

    public function testUnknownDriverFallsBackInBootstrapMode(): void
    {
        $storage = $this->factory->create('s3', true);
        $this->assertNotSame('', $storage->getBasePath());
    }

    public function testDriverFromEngineSettingsDefaultsToLocal(): void
    {
        $this->assertSame('local', StorageFactory::driverFromEngineSettings([]));
        $this->assertSame('local', StorageFactory::driverFromEngineSettings(['storageDriver' => 'unknown']));
    }

    public function testDeploymentModeFallsBackToClassic(): void
    {
        $this->assertSame('classic', StorageFactory::deploymentModeFromEngineSettings([]));
        $this->assertSame('classic', StorageFactory::deploymentModeFromEngineSettings(['deploymentMode' => 'hybrid']));
    }
}
