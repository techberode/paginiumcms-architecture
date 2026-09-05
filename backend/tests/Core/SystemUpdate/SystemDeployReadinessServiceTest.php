<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\SystemUpdate;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\SystemUpdate\Services\SystemDeployReadinessService;
use PaginiumCMS\Core\SystemUpdate\Services\SystemDeployService;
use PaginiumCMS\Tests\Http\TestCase;

final class SystemDeployReadinessServiceTest extends TestCase
{
    public function testReportsDeployDisabledBlocker(): void
    {
        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $settings->setGroup('systemUpdate', array_merge($settings->group('systemUpdate'), [
            'deployEnabled' => false,
            'stackDir' => '/var/lib/docker/compose/paginiumcms',
            'allowDeployTags' => true,
        ]));

        $service = $this->container()->get(SystemDeployReadinessService::class);
        $result = $service->evaluate(true);

        $this->assertFalse($result['ready']);
        $this->assertContains('deploy_disabled', $result['blockers']);
    }

    public function testPrefersStackDirFromSettings(): void
    {
        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $settings->setGroup('systemUpdate', array_merge($settings->group('systemUpdate'), [
            'deployEnabled' => true,
            'stackDir' => '/custom/stack/path',
            'backendPort' => '9090',
            'allowDeployTags' => true,
        ]));

        $deploy = $this->container()->get(SystemDeployService::class);
        $config = $settings->group('systemUpdate');

        $this->assertSame('/custom/stack/path', $deploy->resolvedStackDir($config));
        $this->assertSame('9090', $deploy->resolvedBackendPort($config));
    }
}
