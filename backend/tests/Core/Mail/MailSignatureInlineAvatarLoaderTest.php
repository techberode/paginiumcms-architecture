<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Mail;

use PaginiumCMS\Core\Mail\Services\MailSignatureInlineAvatarLoader;
use PHPUnit\Framework\TestCase;

final class MailSignatureInlineAvatarLoaderTest extends TestCase
{
    private string $contentBase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->contentBase = sys_get_temp_dir() . '/pag_sig_avatar_' . uniqid('', true);
        mkdir($this->contentBase . '/media', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->contentBase);
        parent::tearDown();
    }

    public function testLoadsAllowListedMediaFile(): void
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true
        );
        $this->assertNotFalse($png);
        file_put_contents($this->contentBase . '/media/avatar.png', $png);

        $loaded = MailSignatureInlineAvatarLoader::load(
            'https://site.test/storage/app/content/media/avatar.png',
            $this->contentBase
        );

        $this->assertNotNull($loaded);
        $this->assertSame(MailSignatureInlineAvatarLoader::CONTENT_ID, $loaded['contentId']);
        $this->assertSame('image/png', $loaded['mime']);
        $this->assertSame($png, $loaded['bytes']);
    }

    public function testRejectsPathOutsideMediaTree(): void
    {
        file_put_contents($this->contentBase . '/secret.txt', 'x');

        $this->assertNull(
            MailSignatureInlineAvatarLoader::load('/storage/app/content/secret.txt', $this->contentBase)
        );
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (scandir($path) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $full = $path . '/' . $item;
            if (is_dir($full)) {
                $this->removeTree($full);
            } else {
                unlink($full);
            }
        }
        rmdir($path);
    }
}
