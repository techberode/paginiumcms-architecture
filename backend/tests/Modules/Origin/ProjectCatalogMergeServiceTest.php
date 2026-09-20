<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Origin;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Origin\Services\CatalogDeployStatusResolver;
use PaginiumCMS\Core\Security\Services\EncryptionService;
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
        $probes = (new FeatureProbeRegistry(
            new ProbeSupport(),
            new EncryptionService('base64:BGtLQwdzAE7ajivCghMa98DyudMghYZEkXKw5PJ/aUE=')
        ))->runAll();
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

        $byId = [];
        foreach ($merged['iterations'] as $iteration) {
            $byId[(string) $iteration['id']] = $iteration;
        }

        $this->assertSame(100, $byId['it.78']['percentComplete'] ?? 0);
        $this->assertSame('shipped', $byId['it.78']['phase'] ?? '');
        $this->assertSame(100, $byId['it.79']['percentComplete'] ?? 0);
        $this->assertSame('shipped', $byId['it.83']['phase'] ?? '');
        $this->assertSame(100, $byId['it.72']['percentComplete'] ?? 0);
        $this->assertSame('shipped', $byId['it.86']['phase'] ?? '');
        $this->assertSame(100, $byId['it.58f']['percentComplete'] ?? 0);
        $this->assertSame('shipped', $byId['it.58f']['phase'] ?? '');
        $this->assertSame(100, $byId['it.75']['percentComplete'] ?? 0);
        $this->assertSame(100, $byId['it.48']['percentComplete'] ?? 0);
        $this->assertSame('shipped', $byId['it.48']['phase'] ?? '');

        $checklist87 = null;
        foreach ($merged['checklist']['slices'] as $slice) {
            if (($slice['id'] ?? '') === 'slice-2026-08-30-it87-planner') {
                $checklist87 = $slice;
                break;
            }
        }
        $this->assertIsArray($checklist87);
        $this->assertSame(100, $checklist87['percentComplete']);

        $this->assertSame('2026-09-20', $merged['snapshot']['asOf'] ?? '');
        $this->assertSame('2.1.0-beta.89', $merged['snapshot']['latestTag'] ?? '');
        $this->assertSame('State as of 20 September 2026', $merged['snapshot']['headlineLabel'] ?? '');
        $this->assertCount(3, $merged['snapshot']['groups'] ?? []);
        $this->assertSame('On the latest production tag', $merged['snapshot']['groups'][0]['titleLabel'] ?? '');
        $this->assertNotSame('', $merged['snapshot']['groups'][0]['items'][0]['titleLabel'] ?? '');
        $this->assertArrayHasKey('ops.2026-09-20', $byId);
        $this->assertLessThan(100, $byId['ops.2026-09-20']['percentComplete'] ?? 100);
        $this->assertSame(50, $byId['it.69']['percentComplete'] ?? 0);
        $this->assertSame('partial', $byId['it.69']['phase'] ?? '');
        $this->assertSame($merged['progress']['total'], $merged['progress']['shipped'] + $merged['progress']['partial'] + $merged['progress']['planned']);
    }
}
