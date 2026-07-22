<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Storage;

use PaginiumCMS\Http\Controllers\Storage\StorageController;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class StorageControllerTest extends TestCase
{
    private string $storageRoot;
    private StorageController $controller;

    protected function setUp(): void
    {
        $this->storageRoot = sys_get_temp_dir() . '/paginium_storage_test_' . uniqid('', true);
        mkdir($this->storageRoot . '/app/content/media', 0755, true);
        $this->controller = new StorageController($this->storageRoot);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->storageRoot);
    }

    public function testServeExistingFile(): void
    {
        $relative = 'app/content/media/sample.txt';
        file_put_contents($this->storageRoot . '/' . $relative, 'hello-storage');

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/storage/' . $relative);
        $response = (new ResponseFactory())->createResponse();

        $response = $this->controller->serve($request, $response, ['path' => $relative]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('hello-storage', (string) $response->getBody());
    }

    public function testServeRejectsPathTraversal(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/storage/../secret');
        $response = (new ResponseFactory())->createResponse();

        $response = $this->controller->serve($request, $response, ['path' => '../secret']);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testServeRejectsNonPublicDataFile(): void
    {
        // Regression (audit 2026-07-22): flat-file dáta mimo media podstromu
        // nesmú byť servírované, aj keď fyzicky existujú a nie sú traversal.
        mkdir($this->storageRoot . '/app/content/data/users', 0755, true);
        $relative = 'app/content/data/users/admin.json';
        file_put_contents(
            $this->storageRoot . '/' . $relative,
            '{"passwordHash":"$argon2id$secret","twoFactorSecret":"TOTPSEED"}'
        );

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/storage/' . $relative);
        $response = (new ResponseFactory())->createResponse();

        $response = $this->controller->serve($request, $response, ['path' => $relative]);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringNotContainsString('twoFactorSecret', (string) $response->getBody());
    }

    public function testServeMissingFileReturns404(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/storage/missing.txt');
        $response = (new ResponseFactory())->createResponse();

        $response = $this->controller->serve($request, $response, ['path' => 'missing.txt']);

        $this->assertSame(404, $response->getStatusCode());
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
