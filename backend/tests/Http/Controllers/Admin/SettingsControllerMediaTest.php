<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Tests\Http\TestCase;

final class SettingsControllerMediaTest extends TestCase
{
    public function testAdminCanLoadMediaGroupWithStorageProbe(): void
    {
        $this->loginAsAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/settings/media')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertSame('Media / DAM', $data['data']['schema']['label'] ?? null);
        $this->assertIsArray($data['data']['meta']['storageProbe'] ?? null);
        $this->assertSame('local', $data['data']['meta']['storageProbe']['storageDriver']['active'] ?? null);
        $this->assertSame('available', $data['data']['meta']['storageProbe']['capabilities']['localStorage']['status'] ?? null);
        $this->assertSame('unavailable', $data['data']['meta']['storageProbe']['capabilities']['s3Storage']['status'] ?? null);
    }

    public function testMediaSettingsAcceptStorageDriverField(): void
    {
        $this->loginAsAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('PUT', '/api/admin/settings/media', [
                'storageDriver' => 'local',
                's3Endpoint' => '',
                's3Region' => '',
                's3Bucket' => '',
                's3KeyId' => '',
                's3Secret' => '',
                's3PathStyle' => false,
                's3PublicBaseUrl' => '',
                's3Visibility' => 'private',
                'allowedMimeTypes' => 'image/jpeg,image/png,image/gif,image/webp,image/svg+xml,application/pdf',
                'maxUploadSizeKb' => 5120,
                'stockImagesEnabled' => true,
                'stockImageTopic' => 'tech',
            ])
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_THROW_ON_ERROR));
        $this->assertTrue($data['success']);
        $this->assertSame('local', $data['data']['values']['storageDriver'] ?? null);
    }

    public function testMediaSettingsAllowS3SelectionWithLocalFallback(): void
    {
        $this->loginAsAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('PUT', '/api/admin/settings/media', [
                'storageDriver' => 's3',
                's3Endpoint' => '',
                's3Region' => 'eu-central-1',
                's3Bucket' => 'media-bucket',
                's3KeyId' => 'AKIAEXAMPLE',
                's3Secret' => '',
                's3PathStyle' => false,
                's3PublicBaseUrl' => '',
                's3Visibility' => 'private',
                'allowedMimeTypes' => 'image/jpeg,image/png,image/gif,image/webp,image/svg+xml,application/pdf',
                'maxUploadSizeKb' => 5120,
                'stockImagesEnabled' => true,
                'stockImageTopic' => 'tech',
            ])
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_THROW_ON_ERROR));
        $this->assertTrue($data['success']);
        $this->assertSame('s3', $data['data']['values']['storageDriver'] ?? null);

        $showResponse = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/settings/media')
        );
        $showData = $this->getJsonResponse($showResponse);

        $this->assertSame(200, $showResponse->getStatusCode());
        $this->assertSame('s3', $showData['data']['meta']['storageProbe']['storageDriver']['configured'] ?? null);
        $this->assertSame('local', $showData['data']['meta']['storageProbe']['storageDriver']['active'] ?? null);
        $this->assertSame('fallback', $showData['data']['meta']['storageProbe']['storageDriver']['status'] ?? null);
    }
}
