<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Newsletter;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Modules\Newsletter\Services\NewsletterRepository;
use PHPUnit\Framework\TestCase;

final class NewsletterRepositoryTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/paginium-newsletter-' . bin2hex(random_bytes(4));
        mkdir($this->basePath . '/data/newsletter', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->basePath);
    }

    public function testSubscribeAndExportCsv(): void
    {
        $repo = $this->repository();

        $created = $repo->subscribe('User@Example.com', 'footer');
        $this->assertTrue($created['created']);
        $this->assertSame('user@example.com', $created['email']);
        $this->assertSame('footer', $created['source']);

        $duplicate = $repo->subscribe('user@example.com', 'footer');
        $this->assertFalse($duplicate['created']);

        $counts = $repo->countBySource();
        $this->assertSame(1, $counts['footer'] ?? 0);

        $csv = $repo->exportCsv();
        $this->assertStringContainsString('user@example.com', $csv);
        $this->assertStringContainsString('footer', $csv);
        $this->assertStringStartsWith('id,email,subscribed_at,source', $csv);
    }

    private function repository(): NewsletterRepository
    {
        $validator = new FileValidator($this->basePath);

        return new NewsletterRepository(
            new FileReader($validator),
            new FileWriter($validator)
        );
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
