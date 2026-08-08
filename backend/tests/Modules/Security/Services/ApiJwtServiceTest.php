<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Security\Services;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Modules\Security\Services\ApiJwtDenylistStore;
use PaginiumCMS\Modules\Security\Services\ApiJwtService;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

final class ApiJwtServiceTest extends TestCase
{
    private ApiJwtService $service;

    protected function setUp(): void
    {
        vfsStream::setup('root', null, ['data' => []]);
        $root = vfsStream::url('root');
        $validator = new FileValidator($root);
        $reader = new FileReader($validator);
        $denylist = new ApiJwtDenylistStore($reader);
        $this->service = new ApiJwtService($denylist, 'unit-test-jwt-signing-key-32bytes!!');
    }

    public function testIssueAndVerifyJwtWithMandatoryClaims(): void
    {
        $token = $this->service->issue(['content:read'], 'user:test', 300);
        $claims = $this->service->verify($token);

        $this->assertIsArray($claims);
        $this->assertSame(ApiJwtService::ISSUER, $claims['iss'] ?? null);
        $this->assertSame(ApiJwtService::AUDIENCE, $claims['aud'] ?? null);
        $this->assertSame('user:test', $claims['sub'] ?? null);
        $this->assertSame('content:read', $claims['scope'] ?? null);
        $this->assertNotSame('', $claims['jti'] ?? '');
    }

    public function testRevokedJtiIsRejected(): void
    {
        $token = $this->service->issue(['content:write'], 'api-key:abc', 300);
        $this->assertTrue($this->service->revoke($token));
        $this->assertNull($this->service->verify($token));
    }

    public function testTtlIsCappedAtMax(): void
    {
        $token = $this->service->issue(['content:read'], 'user:test', 3600);
        $claims = $this->service->verify($token);
        $this->assertIsArray($claims);

        $exp = (int) ($claims['exp'] ?? 0);
        $iat = (int) ($claims['iat'] ?? 0);
        $this->assertLessThanOrEqual(ApiJwtService::MAX_TTL_SECONDS, $exp - $iat);
    }
}
