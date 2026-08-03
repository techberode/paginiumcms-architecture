<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Storage;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Storage\Drivers\LocalFlatFileStorage;
use PaginiumCMS\Core\Storage\Services\EngineCapabilityProbe;
use PHPUnit\Framework\TestCase;

final class EngineCapabilityProbeTest extends TestCase
{
    public function testProbeReportsClassicLocalAvailableAndFutureModesUnavailable(): void
    {
        $base = sys_get_temp_dir() . '/pag_engine_probe_' . uniqid('', true);
        mkdir($base, 0777, true);

        $validator = new FileValidator($base);
        $storage = new LocalFlatFileStorage(new FileReader($validator), new FileWriter($validator), $validator);
        $probe = new EngineCapabilityProbe();

        $report = $probe->probe($storage, [
            'deploymentMode' => 'classic',
            'storageDriver' => 'local',
            'schemaValidationEnabled' => true,
            'capabilityProbeEnabled' => true,
        ]);

        $this->assertSame('active', $report['deploymentMode']['status']);
        $this->assertSame('available', $report['capabilities']['localStorage']['status']);
        $this->assertSame('available', $report['capabilities']['classicMode']['status']);
        $this->assertSame('unavailable', $report['capabilities']['hybridMode']['status']);
        $this->assertSame('unavailable', $report['capabilities']['gitHeadlessMode']['status']);
    }

    public function testProbeMarksConfiguredExperimentalModeAsFallback(): void
    {
        $base = sys_get_temp_dir() . '/pag_engine_probe_fb_' . uniqid('', true);
        mkdir($base, 0777, true);

        $validator = new FileValidator($base);
        $storage = new LocalFlatFileStorage(new FileReader($validator), new FileWriter($validator), $validator);
        $probe = new EngineCapabilityProbe();

        $report = $probe->probe($storage, [
            'deploymentMode' => 'hybrid',
            'storageDriver' => 'local',
        ]);

        $this->assertSame('hybrid', $report['deploymentMode']['configured']);
        $this->assertSame('classic', $report['deploymentMode']['active']);
        $this->assertSame('fallback', $report['deploymentMode']['status']);
    }
}
