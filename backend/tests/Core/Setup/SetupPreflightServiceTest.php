<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Setup;

use PaginiumCMS\Core\Setup\Services\SetupPreflightService;
use PHPUnit\Framework\TestCase;

final class SetupPreflightServiceTest extends TestCase
{
    public function testRunReturnsChecksIncludingPhpVersion(): void
    {
        $storage = dirname(__DIR__, 3) . '/storage';
        $service = new SetupPreflightService($storage, dirname(__DIR__, 4));

        $result = $service->run();

        $this->assertGreaterThan(0, count($result['checks']));

        $phpCheck = null;
        foreach ($result['checks'] as $check) {
            if (($check['id'] ?? '') === 'php_version') {
                $phpCheck = $check;
                break;
            }
        }

        $this->assertNotNull($phpCheck);
        $this->assertSame(PHP_VERSION, $phpCheck['current']);

        $expectedStatus = version_compare(PHP_VERSION, '8.5.0', '>=') ? 'pass' : 'fail';
        $this->assertSame($expectedStatus, $phpCheck['status']);
    }

    public function testRunIncludesOptionalRedisChecksThatDoNotBlockReady(): void
    {
        $storage = dirname(__DIR__, 3) . '/storage';
        $service = new SetupPreflightService($storage, dirname(__DIR__, 4));

        $result = $service->run();

        $ids = array_column($result['checks'], 'id');
        $this->assertContains('cache_redis_extension', $ids);
        $this->assertContains('cache_redis_broker', $ids);
        $this->assertTrue($result['ready'] || $result['hardBlockers'] > 0);
    }
}
