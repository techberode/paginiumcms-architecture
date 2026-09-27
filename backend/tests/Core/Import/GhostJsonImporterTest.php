<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Import;

use PaginiumCMS\Core\Import\GhostJsonImporter;
use PHPUnit\Framework\TestCase;

final class GhostJsonImporterTest extends TestCase
{
    public function testParsesPostsExport(): void
    {
        $json = <<<'JSON'
{
  "posts": [
    {
      "title": "Hello Ghost",
      "slug": "hello-ghost",
      "html": "<p>Body</p>",
      "status": "published",
      "published_at": "2024-01-02T08:00:00.000Z",
      "tags": [{"name": "News"}]
    },
    {
      "title": "About",
      "slug": "about",
      "type": "page",
      "html": "<p>About us</p>",
      "status": "published"
    }
  ]
}
JSON;

        $importer = new GhostJsonImporter();
        $rows = $importer->parseJson($json);

        $this->assertCount(2, $rows);
        $this->assertSame('article', $rows[0]['type']);
        $this->assertSame('page', $rows[1]['type']);
        $this->assertSame('ghost', $rows[0]['importSource']);
    }
}
