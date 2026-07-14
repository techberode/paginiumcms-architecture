<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Cache;

use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Core\Cache\Drivers\FileDriver;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

class CacheManagerTest extends TestCase
{
    private CacheManager $cacheManager;
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $structure = ['cache' => []];
        $root = vfsStream::setup('storage', null, $structure);
        $this->root = vfsStream::url('storage');
        $driver = new FileDriver($this->root . '/cache');
        $this->cacheManager = new CacheManager($driver);
    }

    public function testSetAndGet(): void
    {
        $this->cacheManager->set('test_key', 'test_value');
        $this->assertEquals('test_value', $this->cacheManager->get('test_key'));
    }

    public function testGetNonExistent(): void
    {
        $this->assertNull($this->cacheManager->get('non_existent'));
        $this->assertEquals('default', $this->cacheManager->get('non_existent', 'default'));
    }

    public function testHas(): void
    {
        $this->assertFalse($this->cacheManager->has('test_key'));
        $this->cacheManager->set('test_key', 'test_value');
        $this->assertTrue($this->cacheManager->has('test_key'));
    }

    public function testDelete(): void
    {
        $this->cacheManager->set('test_key', 'test_value');
        $this->assertTrue($this->cacheManager->has('test_key'));
        $this->cacheManager->delete('test_key');
        $this->assertFalse($this->cacheManager->has('test_key'));
    }

    public function testClear(): void
    {
        $this->markTestSkipped('vfsStream nepodporuje mazanie súborov.');
    }

    public function testRemember(): void
    {
        $counter = 0;
        $value = $this->cacheManager->remember('remember_key', function () use (&$counter) {
            $counter++;
            return 'computed_value';
        });
        $this->assertEquals('computed_value', $value);
        $this->assertEquals(1, $counter);
        $value = $this->cacheManager->remember('remember_key', function () use (&$counter) {
            $counter++;
            return 'computed_value_again';
        });
        $this->assertEquals('computed_value', $value);
        $this->assertEquals(1, $counter);
    }

    public function testTtl(): void
    {
        $this->cacheManager->set('ttl_key', 'ttl_value', 1);
        $this->assertEquals('ttl_value', $this->cacheManager->get('ttl_key'));
        sleep(2);
        $this->assertNull($this->cacheManager->get('ttl_key'));
    }
}
