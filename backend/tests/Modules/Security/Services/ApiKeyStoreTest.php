<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Security\Services;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Modules\Security\Services\ApiKeyStore;
use PaginiumCMS\Modules\Security\Services\ApiKeyVerifier;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

final class ApiKeyStoreTest extends TestCase
{
    private ApiKeyStore $store;
    private ApiKeyVerifier $verifier;

    protected function setUp(): void
    {
        vfsStream::setup('root', null, ['data' => []]);
        $root = vfsStream::url('root');
        $validator = new FileValidator($root);
        $reader = new FileReader($validator);
        $this->store = new ApiKeyStore($reader);
        $this->verifier = new ApiKeyVerifier($this->store, 'unit-test-pepper');
    }

    public function testCreateStoresVerifierOnlyAndVerifySucceeds(): void
    {
        $created = $this->store->create('CI reader', ['content:read'], null, 'user-1', $this->verifier);

        $this->assertStringStartsWith('pgk_', $created['token']);
        $this->assertNotSame('', $created['record']['secretVerifier']);

        $context = $this->verifier->verifyBearer('Bearer ' . $created['token']);
        $this->assertNotNull($context);
        $this->assertSame($created['record']['id'], $context->id);
        $this->assertTrue($context->hasScope('content:read'));
    }

    public function testListMetadataNeverReturnsVerifier(): void
    {
        $this->store->create('List test', ['settings:read'], null, 'user-1', $this->verifier);
        $rows = $this->store->listMetadata();

        $this->assertCount(1, $rows);
        $this->assertArrayNotHasKey('secretVerifier', $rows[0]);
        $this->assertSame('settings:read', $rows[0]['scopes'][0] ?? null);
    }

    public function testRevokeBlocksVerification(): void
    {
        $created = $this->store->create('Revoke me', ['content:read'], null, 'user-1', $this->verifier);
        $this->assertTrue($this->store->revoke($created['record']['id']));

        $this->assertNull($this->verifier->verifyBearer('Bearer ' . $created['token']));
    }

    public function testRotateRevokesOldKeyAndIssuesNewToken(): void
    {
        $created = $this->store->create('Rotate me', ['content:read', 'content:write'], null, 'user-1', $this->verifier);
        $rotated = $this->store->rotate($created['record']['id'], 'user-2', $this->verifier);

        $this->assertNotNull($rotated);
        $this->assertSame($created['record']['id'], $rotated['previousId']);
        $this->assertNotSame($created['token'], $rotated['token']);
        $this->assertNull($this->verifier->verifyBearer('Bearer ' . $created['token']));
        $this->assertNotNull($this->verifier->verifyBearer('Bearer ' . $rotated['token']));
    }

    public function testPurgeInactiveRemovesRevokedKey(): void
    {
        $created = $this->store->create('Purge me', ['content:read'], null, 'user-1', $this->verifier);
        $this->store->revoke($created['record']['id']);

        $result = $this->store->purgeInactive([$created['record']['id']]);

        $this->assertSame([$created['record']['id']], $result['deleted']);
        $this->assertSame([], $result['skipped']);
        $this->assertSame([], $this->store->listMetadata());
    }

    public function testPurgeInactiveSkipsActiveKey(): void
    {
        $created = $this->store->create('Active', ['content:read'], null, 'user-1', $this->verifier);

        $result = $this->store->purgeInactive([$created['record']['id']]);

        $this->assertSame([], $result['deleted']);
        $this->assertSame([$created['record']['id']], $result['skipped']);
        $this->assertCount(1, $this->store->listMetadata());
    }
}
