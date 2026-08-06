<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Cache;

use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Core\Cache\CacheTagRegistry;
use PaginiumCMS\Core\Cache\ContentCacheService;
use PaginiumCMS\Core\Cache\Drivers\FileDriver;
use PaginiumCMS\Core\Cache\Drivers\MemoryDriver;
use PHPUnit\Framework\TestCase;

final class CacheTagRegistryTest extends TestCase
{
    public function testInvalidatePageTagsRemovesTaggedEntries(): void
    {
        $driver = new MemoryDriver();
        $cache = new CacheManager($driver);
        $service = new ContentCacheService($cache);

        $service->rememberPage('about', 'test-locale', static fn (): array => ['slug' => 'about', 'title' => 'About']);
        $cacheKey = 'content.page.payload.about.' . md5('test-locale');
        $this->assertSame(['slug' => 'about', 'title' => 'About'], $cache->get($cacheKey));

        $service->invalidatePage('about');

        $this->assertNull($cache->get($cacheKey));
    }

    public function testFileDriverTagInvalidation(): void
    {
        $cacheDir = sys_get_temp_dir() . '/paginium-tag-' . bin2hex(random_bytes(4));
        mkdir($cacheDir, 0755, true);

        try {
            $driver = new FileDriver($cacheDir);
            $driver->set('paginium_demo', 'value', 300);
            $driver->tagKey('paginium_demo', [CacheTagRegistry::pagesList()]);

            $this->assertSame('value', $driver->get('paginium_demo'));
            $this->assertSame(1, $driver->invalidateTags([CacheTagRegistry::pagesList()]));
            $this->assertNull($driver->get('paginium_demo', null));
        } finally {
            foreach (scandir($cacheDir) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $path = $cacheDir . DIRECTORY_SEPARATOR . $entry;
                if (is_file($path)) {
                    unlink($path);
                }
            }

            rmdir($cacheDir);
        }
    }
}
