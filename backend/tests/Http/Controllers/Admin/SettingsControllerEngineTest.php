<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Core\Storage\Contracts\StorageInterface;
use PaginiumCMS\Tests\Http\TestCase;

final class SettingsControllerEngineTest extends TestCase
{
    public function testSuperAdminCanLoadEngineGroupWithCapabilityProbe(): void
    {
        $this->loginAsSuperAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/settings/engine')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertSame('Hybrid Engine', $data['data']['schema']['label'] ?? null);
        $this->assertIsArray($data['data']['meta']['capabilityProbe'] ?? null);
        $this->assertSame('available', $data['data']['meta']['capabilityProbe']['capabilities']['localStorage']['status'] ?? null);
    }

    public function testInvalidSettingsOverridesReturn422WithStableFieldErrors(): void
    {
        $this->loginAsSuperAdminUser();

        $storage = $this->container()->get(StorageInterface::class);
        $settingsPath = 'data/settings.testing.json';
        $storage->write(
            $settingsPath,
            json_encode(['general' => 'not-an-object'], JSON_THROW_ON_ERROR)
        );

        try {
            $response = $this->handleRequest(
                $this->createJsonRequest('PUT', '/api/admin/settings/general', [
                    'siteName' => 'Broken Overrides',
                    'language' => 'sk',
                    'timezone' => 'Europe/Bratislava',
                ])
            );
            $data = $this->getJsonResponse($response);

            $this->assertSame(422, $response->getStatusCode());
            $this->assertFalse($data['success']);
            $this->assertIsArray($data['errors'] ?? null);
            $this->assertNotEmpty($data['errors']);
        } finally {
            if ($storage->exists($settingsPath)) {
                $storage->delete($settingsPath, false);
            }
        }
    }

    public function testEngineSettingsRejectNonClassicDeploymentMode(): void
    {
        $this->loginAsSuperAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('PUT', '/api/admin/settings/engine', [
                'deploymentMode' => 'hybrid',
                'storageDriver' => 'local',
                'schemaValidationEnabled' => true,
                'capabilityProbeEnabled' => true,
            ])
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('errors', $data);
    }

    public function testEngineSettingsAcceptPerformanceGuardEnabledWithSampleRate(): void
    {
        $this->loginAsSuperAdminUser();

        $payload = [
            'deploymentMode' => 'classic',
            'storageDriver' => 'local',
            'schemaValidationEnabled' => true,
            'capabilityProbeEnabled' => true,
            'cacheDriver' => 'auto',
            'cacheDefaultTtlSeconds' => 300,
            'httpValidatorsEnabled' => true,
            'gitEnabled' => false,
            'gitPublishStrategy' => 'disabled',
            'gitPublisher' => 'local',
            'gitCommitMessageTemplate' => 'content: publish {count} change(s)',
            'performanceGuardEnabled' => true,
            'performanceGuardSampleRate' => 1.0,
            'performanceGuardLatencyMsWarning' => 200,
            'performanceGuardLatencyMsCritical' => 500,
            'performanceGuardBreachCount' => 3,
            'performanceGuardWindowMinutes' => 10,
            'performanceGuardRemediationMode' => 'suggest',
        ];

        $response = $this->handleRequest(
            $this->createJsonRequest('PUT', '/api/admin/settings/engine', $payload)
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_THROW_ON_ERROR));
        $this->assertTrue($data['success']);
        $this->assertTrue($data['data']['values']['performanceGuardEnabled'] ?? false);
        $this->assertEquals(1.0, $data['data']['values']['performanceGuardSampleRate'] ?? null);
    }
}
