<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Cache;

use PaginiumCMS\Core\Cache\Drivers\RedisDriver;
use PaginiumCMS\Core\Cache\RedisDriverConfig;
use PHPUnit\Framework\TestCase;

final class RedisDriverIntegrationTest extends TestCase
{
    public function testConnectReadWriteWhenRedisAvailable(): void
    {
        if (!extension_loaded('redis')) {
            self::markTestSkipped('ext-redis is not loaded.');
        }

        $host = trim((string) (getenv('REDIS_HOST') ?: getenv('REDIS_TEST_HOST') ?: ''));
        if ($host === '') {
            self::markTestSkipped('Set REDIS_HOST or REDIS_TEST_HOST for integration test.');
        }

        $config = new RedisDriverConfig(
            $host,
            (int) (getenv('REDIS_PORT') ?: 6379),
            (string) (getenv('REDIS_PASSWORD') ?: ''),
            0,
            'paginium-test:'
        );

        $driver = RedisDriver::connect($config);
        self::assertNotNull($driver);

        $key = 'it69-' . bin2hex(random_bytes(4));
        self::assertTrue($driver->set($key, ['n' => 1], 30));
        self::assertSame(['n' => 1], $driver->get($key));
        self::assertTrue($driver->delete($key));
        self::assertTrue($driver->health()['ok']);
    }
}
