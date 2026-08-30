<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Origin;

use PaginiumCMS\Modules\Origin\Services\CatalogDeployStatusResolver;
use PaginiumCMS\Support\AppVersion;
use PHPUnit\Framework\TestCase;

final class CatalogDeployStatusResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        AppVersion::resetCacheForTesting();
        parent::tearDown();
    }

    public function testLiveWhenSinceMatchesRunningVersionAndComplete(): void
    {
        $resolver = new CatalogDeployStatusResolver();
        $status = $resolver->resolveForIteration([
            'since' => AppVersion::VERSION,
            'phase' => 'partial',
        ], 100);

        $this->assertSame('live', $status);
    }

    public function testPendingDeployWhenTargetVersionIsAheadOfInstance(): void
    {
        $resolver = new CatalogDeployStatusResolver();
        $status = $resolver->resolveForIteration([
            'targetVersion' => '9.9.9-beta.99',
            'phase' => 'partial',
        ], 100);

        $this->assertSame('pending_deploy', $status);
    }

    public function testPartialLiveWhenSinceMatchesButItemsIncomplete(): void
    {
        $resolver = new CatalogDeployStatusResolver();
        $status = $resolver->resolveForIteration([
            'since' => AppVersion::VERSION,
            'phase' => 'partial',
        ], 50);

        $this->assertSame('partial_live', $status);
    }

    public function testPlannedPhaseReturnsPlanned(): void
    {
        $resolver = new CatalogDeployStatusResolver();
        $status = $resolver->resolveForIteration([
            'phase' => 'planned',
            'since' => '1.0.0',
        ], 0);

        $this->assertSame('planned', $status);
    }

    public function testRuntimeContextIncludesAppVersion(): void
    {
        $resolver = new CatalogDeployStatusResolver();
        $runtime = $resolver->runtimeContext();

        $this->assertSame(AppVersion::current(), $runtime['appVersion']);
        $this->assertNotSame('', $runtime['environment']);
    }
}
