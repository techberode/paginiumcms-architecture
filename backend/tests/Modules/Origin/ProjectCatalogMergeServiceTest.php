<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Origin;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Origin\Services\CatalogDeployStatusResolver;
use PaginiumCMS\Modules\Origin\Services\FeatureProbeRegistry;
use PaginiumCMS\Modules\Origin\Services\ImplementationChecklistReader;
use PaginiumCMS\Modules\Origin\Services\OriginCatalogLabelResolver;
use PaginiumCMS\Modules\Origin\Services\ProjectCatalogMergeService;
use PaginiumCMS\Modules\Origin\Services\ProjectCatalogReader;
use PaginiumCMS\Modules\Origin\Services\ProbeSupport;
use PHPUnit\Framework\TestCase;

final class ProjectCatalogMergeServiceTest extends TestCase
{
    private function service(): ProjectCatalogMergeService
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->with('general.language')->willReturn('en');

        return new ProjectCatalogMergeService(
            new ProjectCatalogReader(),
            new CatalogDeployStatusResolver(),
            new ImplementationChecklistReader(),
            new OriginCatalogLabelResolver($settings),
        );
    }

    public function testMergeComputesPercentFromProbesAndCatalog(): void
    {
        $probes = (new FeatureProbeRegistry(new ProbeSupport()))->runAll();
        $merged = $this->service()->merge($probes);

        $this->assertSame(1, $merged['schemaVersion']);
        $this->assertNotEmpty($merged['iterations']);
        $this->assertGreaterThanOrEqual(0, $merged['progress']['percent']);
        $this->assertGreaterThan(0, $merged['progress']['total']);
        $this->assertNotSame('', $merged['runtime']['appVersion']);
        $this->assertGreaterThanOrEqual(0, $merged['progress']['liveOnInstance']);
        $this->assertNotEmpty($merged['checklist']['slices']);

        $it81 = null;
        foreach ($merged['iterations'] as $iteration) {
            if ($iteration['id'] === 'it.81') {
                $it81 = $iteration;
                break;
            }
        }

        $this->assertIsArray($it81);
        $this->assertSame(100, $it81['percentComplete']);
        $this->assertArrayHasKey('deployStatus', $it81);
        $this->assertSame('It.81 Editorial workflow', $it81['titleLabel']);
        $this->assertCount(6, $it81['items']);
        $this->assertNotSame('', $it81['items'][0]['titleLabel'] ?? '');
    }
}
