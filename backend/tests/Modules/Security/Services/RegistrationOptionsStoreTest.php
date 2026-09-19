<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Security\Services;

use InvalidArgumentException;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Modules\Security\Services\RegistrationOptionsStore;
use PaginiumCMS\Modules\Security\Services\RoleRepository;
use PHPUnit\Framework\TestCase;

final class RegistrationOptionsStoreTest extends TestCase
{
    private string $baseDir;
    private RegistrationOptionsStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . '/pag_regopt_' . uniqid('', true);
        mkdir($this->baseDir . '/data', 0777, true);
        $validator = new FileValidator($this->baseDir);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $roles = new RoleRepository($reader, $writer);
        $roles->save('USER', 'User', ['profile:edit'], true);
        $roles->save('EDITOR', 'Editor', ['content:edit'], true);
        $this->store = new RegistrationOptionsStore($reader, $writer, $roles);
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->baseDir);
        parent::tearDown();
    }

    public function testPublicListEmptyUntilSeeded(): void
    {
        $this->assertSame([], $this->store->publicList());
        $seeded = $this->store->seedIfEmpty();
        $this->assertNotSame([], $seeded);
        $this->assertNotSame([], $this->store->publicList());
    }

    public function testRejectsAdminRole(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->store->save([
            [
                'label' => 'Root',
                'roleId' => 'ADMIN',
                'enabled' => true,
            ],
        ]);
    }

    public function testPersistsWelcomeMailAndApproval(): void
    {
        $saved = $this->store->save([
            [
                'label' => 'Publicist',
                'roleId' => 'USER',
                'enabled' => true,
                'requireAdminApproval' => true,
                'welcomeMailEnabled' => true,
                'welcomeMailSubject' => 'Approved',
                'welcomeMailBody' => 'You can sign in.',
            ],
        ]);
        $this->assertCount(1, $saved);
        $this->assertTrue($saved[0]['requireAdminApproval']);
        $this->assertSame('Approved', $saved[0]['welcomeMailSubject']);
        $this->assertSame('Publicist', $this->store->publicList()[0]['label']);
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
