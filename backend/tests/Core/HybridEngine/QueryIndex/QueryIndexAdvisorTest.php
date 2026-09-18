<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\HybridEngine\QueryIndex;

use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexAdvisor;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexInterface;
use PaginiumCMS\Core\Performance\PerformanceAggregator;
use PaginiumCMS\Core\Performance\PerformanceGuardSettings;
use PaginiumCMS\Core\Performance\PerformanceSampleStore;
use PaginiumCMS\Core\Performance\SafeRemediationService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Support\JsonHelper;
use PHPUnit\Framework\TestCase;

final class QueryIndexAdvisorTest extends TestCase
{
    private string $samplesPath;

    protected function setUp(): void
    {
        $this->samplesPath = sys_get_temp_dir() . '/apm-adv-' . uniqid('', true) . '.json';
    }

    protected function tearDown(): void
    {
        @unlink($this->samplesPath);
    }

    public function testDoesNotHintWhenDriverIsSqlite(): void
    {
        $advisor = $this->makeAdvisor(
            engine: [
                'queryIndexDriver' => 'sqlite',
                'queryIndexAdviseEnabled' => true,
                'queryIndexAdviseMinEntries' => 10,
                'performanceGuardEnabled' => true,
            ],
            entryCount: 100,
            samples: [$this->sample('/api/articles', 500.0)],
        );

        $this->assertNull($advisor->evaluateForRoute('/api/articles'));
    }

    public function testHintsWhenCatalogSlowAndLargeEnough(): void
    {
        $advisor = $this->makeAdvisor(
            engine: [
                'queryIndexDriver' => 'json',
                'queryIndexAdviseEnabled' => true,
                'queryIndexAdviseMinEntries' => 10,
                'queryIndexAdviseListP95Ms' => 100,
                'performanceGuardEnabled' => true,
                'performanceGuardLatencyMsWarning' => 200,
            ],
            entryCount: 50,
            samples: array_fill(0, 5, $this->sample('/api/articles', 250.0)),
        );

        $hint = $advisor->evaluateForRoute('/api/articles');
        $this->assertNotNull($hint);
        $this->assertSame(QueryIndexAdvisor::HINT_CODE, $hint['code']);
    }

    /**
     * @param array<string, mixed> $engine
     * @param list<array<string, mixed>> $samples
     */
    private function makeAdvisor(array $engine, int $entryCount, array $samples): QueryIndexAdvisor
    {
        file_put_contents($this->samplesPath, JsonHelper::encode($samples));

        $settingsRepo = $this->createMock(SettingsRepositoryInterface::class);
        $settingsRepo->method('group')->willReturnCallback(static function (string $group) use ($engine) {
            return $group === 'engine' ? $engine : [];
        });

        $queryIndex = $this->createMock(QueryIndexInterface::class);
        $queryIndex->method('entryCount')->willReturn($entryCount);

        $root = sys_get_temp_dir();
        $writer = new FileWriter(new FileValidator($root));
        $store = new PerformanceSampleStore($writer, $this->samplesPath);

        return new QueryIndexAdvisor(
            $settingsRepo,
            new PerformanceGuardSettings($settingsRepo),
            $store,
            new PerformanceAggregator($store),
            $queryIndex
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function sample(string $route, float $durationMs): array
    {
        return [
            'ts' => time(),
            'route' => $route,
            'method' => 'GET',
            'status' => 200,
            'duration_ms' => $durationMs,
            'memory_delta_mb' => 0.1,
            'storage_reads' => 1,
            'storage_writes' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
        ];
    }
}
