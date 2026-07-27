<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Demo;

use PaginiumCMS\Modules\Demo\Data\DemoFixtures;
use PHPUnit\Framework\TestCase;

final class DemoFixturesTest extends TestCase
{
    public function testSeedFilesIncludeCommentsMessagesAndNewsletter(): void
    {
        $files = DemoFixtures::seedFiles();

        $this->assertArrayHasKey('data/comments.json', $files);
        $this->assertArrayHasKey('data/newsletter/subscribers.json', $files);
        $this->assertArrayHasKey('pages/contact.md', $files);
        $this->assertArrayHasKey('data/messages/demo_msg_1.json', $files);

        $comments = json_decode($files['data/comments.json'], true);
        $this->assertIsArray($comments);
        $this->assertSame('approved', $comments[0]['status'] ?? null);
        $this->assertSame('uvod-do-flatfile', $comments[0]['articleSlug'] ?? null);
    }
}
