<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Origin;

use PaginiumCMS\Modules\Origin\Services\FeatureProbeRegistry;
use PaginiumCMS\Modules\Origin\Services\ProjectCatalogMergeService;
use PaginiumCMS\Modules\Origin\Services\ProjectCatalogReader;
use PaginiumCMS\Modules\Origin\Services\ProbeSupport;
use PHPUnit\Framework\TestCase;

final class ProjectCatalogMergeServiceTest extends TestCase
{
    public function testMergeComputesPercentFromProbesAndCatalog(): void
    {
        $probes = (new FeatureProbeRegistry(new ProbeSupport()))->runAll();
        $service = new ProjectCatalogMergeService(new ProjectCatalogReader());
        $merged = $service->merge($probes);

        $this->assertSame(1, $merged['schemaVersion']);
        $this->assertNotEmpty($merged['iterations']);
        $this->assertGreaterThanOrEqual(0, $merged['progress']['percent']);
        $this->assertGreaterThan(0, $merged['progress']['total']);

        $it81 = null;
        foreach ($merged['iterations'] as $iteration) {
            if ($iteration['id'] === 'it.81') {
                $it81 = $iteration;
                break;
            }
        }

        $this->assertIsArray($it81);
        $this->assertSame(100, $it81['percentComplete']);
        $this->assertCount(6, $it81['items']);
    }
}
