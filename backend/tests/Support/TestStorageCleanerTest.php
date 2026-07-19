<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Support;

use PHPUnit\Framework\TestCase;

final class TestStorageCleanerTest extends TestCase
{
    private string $tempRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempRoot = sys_get_temp_dir() . '/paginium_cleaner_' . uniqid('', true);
        mkdir($this->tempRoot, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteTree($this->tempRoot);
        parent::tearDown();
    }

    public function testDeletesExampleComUsersAndKeepsProductionEmail(): void
    {
        $usersDir = $this->tempRoot . '/users';
        mkdir($usersDir, 0777, true);

        $testUser = $usersDir . '/user_test.json';
        file_put_contents($testUser, json_encode(['email' => 'test_abc@example.com'], JSON_THROW_ON_ERROR));
        file_put_contents($testUser . '.backup.20260719_120000', '{}');

        $realUser = $usersDir . '/user_real.json';
        file_put_contents($realUser, json_encode(['email' => 'admin@mycompany.sk'], JSON_THROW_ON_ERROR));

        $this->purgeUsersInDir($usersDir);

        self::assertFileDoesNotExist($testUser);
        self::assertFileDoesNotExist($testUser . '.backup.20260719_120000');
        self::assertFileExists($realUser);
    }

    public function testDeletesTestPageSlugFiles(): void
    {
        $pagesDir = $this->tempRoot . '/pages';
        mkdir($pagesDir, 0777, true);

        $testPage = $pagesDir . '/seo-test-abc.md';
        $realPage = $pagesDir . '/about-us.md';
        file_put_contents($testPage, '# test');
        file_put_contents($realPage, '# about');

        $this->purgePagesInDir($pagesDir);

        self::assertFileDoesNotExist($testPage);
        self::assertFileExists($realPage);
    }

    private function purgeUsersInDir(string $usersDir): void
    {
        foreach (glob($usersDir . '/user_*.json') ?: [] as $file) {
            $raw = (string) file_get_contents($file);
            if (!str_contains($raw, '@example.com')) {
                continue;
            }
            unlink($file);
            foreach (glob($file . '.backup.*') ?: [] as $backup) {
                unlink($backup);
            }
        }
    }

    private function purgePagesInDir(string $pagesDir): void
    {
        foreach (glob($pagesDir . '/*') ?: [] as $file) {
            $slug = preg_replace('/\.(md|json)$/i', '', basename($file)) ?? '';
            if (preg_match('/^(seo-test-|bulk-a-)/', $slug) === 1) {
                unlink($file);
            }
        }
    }

    private function deleteTree(string $dir): void
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
                $this->deleteTree($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
