<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Media\Services;

use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;
use PaginiumCMS\Modules\Media\Services\S3MediaStorageConfig;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class S3MediaStorageConfigTest extends TestCase
{
    public function testFromSettingsRequiresCoreFields(): void
    {
        $guard = new OutboundUrlGuard(true, true);

        $this->expectException(RuntimeException::class);
        S3MediaStorageConfig::fromSettings([
            's3Bucket' => 'bucket',
            's3Region' => 'eu-central-1',
            's3KeyId' => 'key',
            's3Secret' => '',
        ], $guard);
    }

    public function testRedactedSummaryNeverIncludesSecret(): void
    {
        $config = S3MediaStorageConfig::fromSettings([
            's3Bucket' => 'bucket',
            's3Region' => 'eu-central-1',
            's3KeyId' => 'key-id',
            's3Secret' => 'super-secret',
            's3Endpoint' => 'https://s3.example.com',
            's3PublicBaseUrl' => 'https://cdn.example.com',
            's3Visibility' => 'private',
        ], new OutboundUrlGuard(true, true));

        $summary = $config->redactedSummary();

        $this->assertSame('bucket', $summary['bucket']);
        $this->assertTrue($summary['credentialsConfigured']);
        $this->assertArrayNotHasKey('secret', $summary);
        $this->assertArrayNotHasKey('keyId', $summary);
    }

    public function testRejectsJavascriptPublicBaseUrl(): void
    {
        $this->expectException(RuntimeException::class);

        S3MediaStorageConfig::fromSettings([
            's3Bucket' => 'bucket',
            's3Region' => 'eu-central-1',
            's3KeyId' => 'key-id',
            's3Secret' => 'secret',
            's3PublicBaseUrl' => 'javascript:alert(1)',
        ], new OutboundUrlGuard(true, true));
    }
}
