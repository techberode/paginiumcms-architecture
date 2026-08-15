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

    public function testSemverFromDescribeParsesTaggedAndDirtyOutput(): void
    {
        $this->assertSame('2.1.0-beta.47', AppVersion::semverFromDescribe('v2.1.0-beta.47'));
        $this->assertSame('2.1.0-beta.47', AppVersion::semverFromDescribe('2.1.0-beta.47-1-g1224e3a'));
        $this->assertNull(AppVersion::semverFromDescribe('1224e3a'));
    }

    public function testVersionConstantIsSemverShaped(): void
    {
        $this->assertMatchesRegularExpression(
            '/^\d+\.\d+\.\d+(-[a-zA-Z0-9.]+)?$/',
            AppVersion::VERSION
        );
    }
}
