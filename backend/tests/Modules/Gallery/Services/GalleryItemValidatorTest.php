<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Gallery\Services;

use PaginiumCMS\Modules\Gallery\Models\GalleryItem;
use PaginiumCMS\Modules\Gallery\Services\GalleryItemValidator;
use PHPUnit\Framework\TestCase;

final class GalleryItemValidatorTest extends TestCase
{
    private GalleryItemValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new GalleryItemValidator();
    }

    public function testValidPayload(): void
    {
        $this->assertNull($this->validator->validate([
            'title' => 'Dashboard',
            'description' => 'Overview',
            'mediaPath' => '/storage/media/dashboard.webp',
            'featureTag' => 'analytics',
            'linkUrl' => 'https://example.com/docs',
            'status' => GalleryItem::STATUS_PUBLISHED,
        ]));
    }

    public function testRejectsInvalidMediaPath(): void
    {
        $this->assertNotNull($this->validator->validate([
            'title' => 'Bad',
            'mediaPath' => '../secret.png',
        ]));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unsafeLinkUrls(): iterable
    {
        yield 'javascript comment bypass' => ['javascript://example.com/%0aalert(1)'];
        yield 'javascript plain' => ['javascript:alert(1)'];
        yield 'data uri' => ['data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg=='];
        yield 'vbscript' => ['vbscript://host/%0amsgbox(1)'];
        yield 'protocol relative' => ['//evil.example/phish'];
        yield 'ftp' => ['ftp://example.com/file'];
        yield 'embedded tab' => ["java\tscript://example.com/%0aalert(1)"];
        yield 'backslash host' => ['/\\evil.example'];
    }

    /**
     * @dataProvider unsafeLinkUrls
     */
    public function testRejectsUnsafeLinkUrl(string $url): void
    {
        $this->assertNotNull($this->validator->validate([
            'title' => 'XSS',
            'mediaPath' => '/storage/media/ok.png',
            'linkUrl' => $url,
        ]));
    }

    public function testAcceptsRelativeAndHttpLinkUrls(): void
    {
        foreach (['/blog/post', 'http://example.com', 'https://example.com/a?b=c#d'] as $url) {
            $this->assertNull($this->validator->validate([
                'title' => 'Ok',
                'mediaPath' => '/storage/media/ok.png',
                'linkUrl' => $url,
            ]), $url);
        }
    }

    public function testRejectsMissingTitle(): void
    {
        $this->assertNotNull($this->validator->validate([
            'title' => '',
            'mediaPath' => '/storage/media/ok.png',
        ]));
    }
}
