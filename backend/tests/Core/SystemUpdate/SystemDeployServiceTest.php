<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\SystemUpdate;

use InvalidArgumentException;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\SystemUpdate\Services\SystemDeployService;
use PaginiumCMS\Tests\Http\TestCase;

final class SystemDeployServiceTest extends TestCase
{
    public function testDeploySkippedInTestingEnvironment(): void
    {
        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $settings->setGroup('systemUpdate', array_merge($settings->group('systemUpdate'), [
            'deployEnabled' => true,
            'allowDeployTags' => true,
        ]));

        $service = new SystemDeployService($settings, dirname(__DIR__, 4));
        $result = $service->deploy(['ref' => 'v2.1.0-beta.12']);

        $this->assertFalse($result->success);
        $this->assertSame('testing_skipped', $result->reason);
    }

    public function testAssertAllowedRefAcceptsSemverTagWhenEnabled(): void
    {
        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $service = new SystemDeployService($settings);

        $service->assertAllowedRef('v2.1.0-beta.12', [
            'allowDeployTags' => true,
            'allowDeployMain' => false,
        ]);

        $this->addToAssertionCount(1);
    }

    public function testAssertAllowedRefRejectsBranchWhenDisabled(): void
    {
        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $service = new SystemDeployService($settings);

        $this->expectException(InvalidArgumentException::class);
        $service->assertAllowedRef('origin/main', [
            'allowDeployTags' => true,
            'allowDeployMain' => false,
        ]);
    }

    public function testAssertAllowedRefRejectsShellInjection(): void
    {
        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $service = new SystemDeployService($settings);

        $this->expectException(InvalidArgumentException::class);
        $service->assertAllowedRef('origin/main; rm -rf /', [
            'allowDeployTags' => true,
            'allowDeployMain' => true,
        ]);
    }
}
