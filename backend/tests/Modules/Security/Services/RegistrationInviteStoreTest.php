<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Security\Services;

use InvalidArgumentException;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Modules\Security\Services\RegistrationInviteStore;
use PHPUnit\Framework\TestCase;

final class RegistrationInviteStoreTest extends TestCase
{
    private string $baseDir;
    private RegistrationInviteStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . '/pag_reginv_' . uniqid('', true);
        mkdir($this->baseDir . '/data', 0777, true);
        $validator = new FileValidator($this->baseDir);
        $this->store = new RegistrationInviteStore(
            new FileReader($validator),
            new FileWriter($validator)
        );
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->baseDir);
        parent::tearDown();
    }

    public function testCreatePeekConsumeAndRevoke(): void
    {
        $created = $this->store->create('Dev@Example.com', '', 'user_admin', 'admin');
        $this->assertSame('dev@example.com', $created['record']['email']);
        $this->assertSame(1, count($this->store->list()));
        $found = $this->store->findUsableByToken((string) $created['token']);
        $this->assertNotNull($found);
        $this->store->consume((string) $created['token'], 'dev@example.com', 'user_new');
        $this->assertNull($this->store->findUsableByToken((string) $created['token']));
        $listed = $this->store->list()[0];
        $this->assertGreaterThan(0, (int) $listed['usedAt']);
        $this->store->revoke((string) $created['id']);
        $this->assertSame([], $this->store->list());
    }

    public function testWrongEmailDoesNotConsume(): void
    {
        $created = $this->store->create('dev@example.com', '', 'user_admin', 'admin');
        $this->expectException(InvalidArgumentException::class);
        $this->store->consume((string) $created['token'], 'other@example.com', 'user_new');
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($path);
    }
}
