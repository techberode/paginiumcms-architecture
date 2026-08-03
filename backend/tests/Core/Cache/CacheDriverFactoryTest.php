<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Cache;

use PaginiumCMS\Core\Cache\CacheDriverFactory;
use PaginiumCMS\Core\Cache\Drivers\ChainedDriver;
use PaginiumCMS\Core\Cache\Drivers\FileDriver;
use PaginiumCMS\Core\Cache\Drivers\MemoryDriver;
use PHPUnit\Framework\TestCase;

final class CacheDriverFactoryTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/paginium-cache-factory-' . bin2hex(random_bytes(4));
        mkdir($this->cacheDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->cacheDir)) {
            array_map('unlink', glob($this->cacheDir . '/*') ?: []);
            rmdir($this->cacheDir);
        }
    }

    public function testDefaultAutoCreatesChainedDriver(): void
    {
        $factory = new CacheDriverFactory($this->cacheDir);
        $driver = $factory->create(null);

        $this->assertInstanceOf(ChainedDriver::class, $driver);
        $health = $driver->health();
        $this->assertTrue($health['ok']);
    }

    public function testFileDriverCreatesFileDriver(): void
    {
        $factory = new CacheDriverFactory($this->cacheDir);
        $driver = $factory->create('file');

        $this->assertInstanceOf(FileDriver::class, $driver);
    }

    public function testRedisFallsBackToAuto(): void
    {
        $this->assertSame('auto', CacheDriverFactory::normalizeDriver('redis'));
        $this->assertSame('auto', CacheDriverFactory::driverFromEngineSettings(['cacheDriver' => 'redis']));
    }

    public function testMemoryDriverHealthProbe(): void
    {
        $factory = new CacheDriverFactory($this->cacheDir);
        $driver = $factory->create('memory');

        $this->assertInstanceOf(MemoryDriver::class, $driver);
        $this->assertTrue($driver->health()['ok']);
    }
}
