<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Support;

use PaginiumCMS\Support\AppVersion;
use PHPUnit\Framework\TestCase;

final class AppVersionTest extends TestCase
{
    protected function tearDown(): void
    {
        AppVersion::resetCacheForTesting();
        parent::tearDown();
    }

    public function testCurrentReturnsSemverShapedVersion(): void
    {
        $this->assertMatchesRegularExpression(
            '/^\d+\.\d+\.\d+(-[a-zA-Z0-9.]+)?$/',
            AppVersion::current()
        );
    }
}
