<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Support;

use PHPUnit\Framework\TestCase;

final class TestStorageCleanerGuardTest extends TestCase
{
    protected function setUp(): void
    {
        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';
    }

    public function testPurgeAllUsersForTestingRefusesOutsideTestingEnv(): void
    {
        putenv('APP_ENV=development');
        $_ENV['APP_ENV'] = 'development';

        $this->assertFalse(TestStorageCleaner::purgeAllUsersForTesting());
    }

    public function testIsTestUserPayloadTreatsExampleComAsTestOnly(): void
    {
        $this->assertTrue($this->invokeIsTestUserPayload('{"email":"qa@example.com"}'));
        $this->assertFalse($this->invokeIsTestUserPayload('{"email":"admin@paginium.local"}'));
    }

    private function invokeIsTestUserPayload(string $raw): bool
    {
        $method = new \ReflectionMethod(TestStorageCleaner::class, 'isTestUserPayload');

        return $method->invoke(null, $raw);
    }
}
