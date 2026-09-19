<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Messages\Services;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Modules\Messages\Services\MessageRoutingStore;
use PHPUnit\Framework\TestCase;

final class MessageRoutingStoreTest extends TestCase
{
    private string $baseDir;
    private MessageRoutingStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . '/pag_msg_route_' . uniqid('', true);
        mkdir($this->baseDir . '/data', 0777, true);
        $validator = new FileValidator($this->baseDir);
        $this->store = new MessageRoutingStore(new FileReader($validator), new FileWriter($validator));
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->baseDir);
        parent::tearDown();
    }

    public function testMatchIgnoresDisabledMasterSwitch(): void
    {
        $this->store->save([
            'enabled' => false,
            'routes' => [
                ['subject' => 'Technická podpora', 'enabled' => true, 'teamIds' => ['team_1'], 'userIds' => []],
            ],
        ]);

        $this->assertNull($this->store->match('Technická podpora'));
    }

    public function testMatchIsCaseInsensitive(): void
    {
        $this->store->save([
            'enabled' => true,
            'routes' => [
                ['subject' => 'Technická podpora', 'enabled' => true, 'teamIds' => ['team_1'], 'userIds' => ['user_1']],
            ],
        ]);

        $route = $this->store->match('technická podpora');
        $this->assertNotNull($route);
        $this->assertSame(['user_1'], $route['userIds']);
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
