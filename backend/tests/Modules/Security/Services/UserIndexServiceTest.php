<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Security\Services;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Modules\Security\Services\UserIndexService;
use PaginiumCMS\Support\FileHelper;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

final class UserIndexServiceTest extends TestCase
{
    private string $root;
    private UserIndexService $index;

    protected function setUp(): void
    {
        parent::setUp();

        vfsStream::setup('storage', null, [
            'data' => [
                'users' => [],
                'index' => [],
            ],
        ]);

        $this->root = vfsStream::url('storage');
        $validator = new FileValidator($this->root . '/data');
        $reader = new FileReader($validator);
        $this->index = new UserIndexService($reader, 'index/users.json');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createUserFile(string $id, array $data): void
    {
        $filePath = $this->root . '/data/users/' . $id . '.json';
        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function testRebuildIndexesExistingUsers(): void
    {
        $this->createUserFile('user_a', [
            'id' => 'user_a',
            'email' => 'Alice@Example.com',
            'username' => 'Alice',
            'passwordHash' => 'hash',
        ]);
        $this->createUserFile('user_b', [
            'id' => 'user_b',
            'email' => 'bob@example.com',
            'username' => 'bob',
            'passwordHash' => 'hash',
        ]);

        $this->index->rebuild('users');

        $this->assertSame('user_a', $this->index->resolveIdByEmail('alice@example.com'));
        $this->assertSame('user_b', $this->index->resolveIdByUsername('bob'));
        $this->assertSame(['user_a', 'user_b'], $this->index->listIds());
    }

    public function testEnsureBuiltCreatesIndexFromDisk(): void
    {
        $this->createUserFile('legacy_user', [
            'id' => 'legacy_user',
            'email' => 'legacy@example.com',
            'username' => 'legacy',
            'passwordHash' => 'hash',
        ]);

        $this->index->ensureBuilt('users');

        $this->assertSame('legacy_user', $this->index->resolveIdByEmail('legacy@example.com'));
        $this->assertFileExists($this->root . '/data/index/users.json');
    }

    public function testUpsertUpdatesEmailAndUsernameMappings(): void
    {
        $this->index->upsertFromRaw([
            'id' => 'user_1',
            'email' => 'old@example.com',
            'username' => 'oldname',
        ]);

        $this->index->upsertFromRaw([
            'id' => 'user_1',
            'email' => 'new@example.com',
            'username' => 'newname',
        ]);

        $this->assertNull($this->index->resolveIdByEmail('old@example.com'));
        $this->assertSame('user_1', $this->index->resolveIdByEmail('new@example.com'));
        $this->assertNull($this->index->resolveIdByUsername('oldname'));
        $this->assertSame('user_1', $this->index->resolveIdByUsername('newname'));
    }

    public function testResolveIdByResetTokenHashRespectsExpiry(): void
    {
        $token = 'valid_reset_token_1234567890';
        $hash = hash('sha256', $token);

        $this->index->upsertFromRaw([
            'id' => 'user_reset',
            'email' => 'reset@example.com',
            'username' => 'reset',
            'resetTokenHash' => $hash,
            'resetTokenExpires' => time() + 3600,
        ]);

        $this->assertSame('user_reset', $this->index->resolveIdByResetTokenHash($hash, time()));

        $expiredHash = hash('sha256', 'expired_token');
        $this->index->upsertFromRaw([
            'id' => 'user_expired',
            'email' => 'expired@example.com',
            'username' => 'expired',
            'resetTokenHash' => $expiredHash,
            'resetTokenExpires' => time() - 60,
        ]);

        $this->assertNull($this->index->resolveIdByResetTokenHash($expiredHash, time()));
    }

    public function testRemoveClearsAllMappings(): void
    {
        $this->index->upsertFromRaw([
            'id' => 'user_delete',
            'email' => 'delete@example.com',
            'username' => 'delete',
        ]);

        $this->index->remove('user_delete');

        $this->assertFalse($this->index->hasId('user_delete'));
        $this->assertNull($this->index->resolveIdByEmail('delete@example.com'));
        $this->assertNull($this->index->resolveIdByUsername('delete'));
    }

    public function testRebuildIgnoresBackupFiles(): void
    {
        $this->createUserFile('user_real', [
            'id' => 'user_real',
            'email' => 'real@example.com',
            'username' => 'real',
            'passwordHash' => 'hash',
        ]);

        file_put_contents(
            $this->root . '/data/users/user_real.json.backup.20260718_120000',
            json_encode(['id' => 'user_backup', 'email' => 'backup@example.com'])
        );

        $this->index->rebuild('users');

        $this->assertSame(['user_real'], $this->index->listIds());
        $stored = FileHelper::readJson($this->root . '/data/index/users.json');
        $this->assertSame('user_real', $stored['by_email']['real@example.com'] ?? null);
    }
}
