<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Media;

use PaginiumCMS\Core\Media\Services\DamMediaUrl;
use PHPUnit\Framework\TestCase;

final class DamMediaUrlTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function allowedUrls(): iterable
    {
        yield 'storage path' => ['/storage/app/content/media/hero.webp'];
        yield 'media api path' => ['/api/media/file/media/2026/a.png'];
        yield 'query string' => ['/storage/app/content/media/a.png?v=2'];
        yield 'absolute https' => ['https://cdn.example.com/storage/app/content/media/a.png'];
        yield 'absolute http' => ['http://localhost:8080/api/media/file/media/a.png'];
    }

    /**
     * @dataProvider allowedUrls
     */
    public function testAllowListedMediaUrlIsKept(string $url): void
    {
        $this->assertSame($url, DamMediaUrl::sanitize($url));
    }

    public function testWhitespaceIsTrimmed(): void
    {
        $this->assertSame('/storage/a.png', DamMediaUrl::sanitize("  /storage/a.png\n"));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function rejectedUrls(): iterable
    {
        yield 'empty' => [''];
        yield 'javascript' => ['javascript:alert(1)'];
        yield 'data uri' => ['data:image/svg+xml;base64,PHN2Zz4='];
        yield 'protocol relative' => ['//evil.example/storage/a.png'];
        yield 'relative' => ['storage/a.png'];
        yield 'other public path' => ['/admin/settings'];
        yield 'private data tree' => ['/data/users.json'];
        yield 'traversal' => ['/storage/../data/users.json'];
        yield 'encoded traversal' => ['/storage/%2e%2e/data/users.json'];
        yield 'absolute traversal' => ['https://cdn.example.com/storage/../data/x'];
        yield 'backslash' => ['/storage\\..\\data\\x'];
        yield 'control char' => ["/storage/a.png\x00.php"];
        yield 'ftp scheme' => ['ftp://example.com/storage/a.png'];
        yield 'absolute non-media path' => ['https://example.com/wp-admin/x.png'];
    }

    /**
     * @dataProvider rejectedUrls
     */
    public function testUnsafeOrForeignUrlIsRejected(string $url): void
    {
        $this->assertSame('', DamMediaUrl::sanitize($url));
    }
}
