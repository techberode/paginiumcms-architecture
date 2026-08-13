<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Import;

use PaginiumCMS\Core\Import\WordPressWxrImporter;
use PHPUnit\Framework\TestCase;

final class WordPressWxrImporterTest extends TestCase
{
    private WordPressWxrImporter $importer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importer = new WordPressWxrImporter();
    }

    public function testParsesPostAndPageItems(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"
    xmlns:content="http://purl.org/rss/1.0/modules/content/"
    xmlns:wp="http://wordpress.org/export/1.2/"
    xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/">
  <channel>
    <item>
      <title>Hello post</title>
      <content:encoded><![CDATA[<p>Body</p>]]></content:encoded>
      <excerpt:encoded><![CDATA[Short excerpt]]></excerpt:encoded>
      <wp:post_type>post</wp:post_type>
      <wp:status>publish</wp:status>
      <wp:post_name>hello-post</wp:post_name>
      <wp:post_date>2024-05-01 10:00:00</wp:post_date>
      <category domain="post_tag" nicename="news">News</category>
    </item>
    <item>
      <title>About page</title>
      <content:encoded><![CDATA[<p>About us</p>]]></content:encoded>
      <wp:post_type>page</wp:post_type>
      <wp:status>publish</wp:status>
      <wp:post_name>about-us</wp:post_name>
      <wp:post_date>2024-05-02 12:00:00</wp:post_date>
    </item>
    <item>
      <title>Attachment</title>
      <wp:post_type>attachment</wp:post_type>
      <wp:status>inherit</wp:status>
      <wp:post_name>photo</wp:post_name>
    </item>
  </channel>
</rss>
XML;

        $rows = $this->importer->parseXml($xml);

        $this->assertCount(2, $rows);
        $this->assertSame('article', $rows[0]['type']);
        $this->assertSame('hello-post', $rows[0]['slug']);
        $this->assertSame('published', $rows[0]['status']);
        $this->assertSame(['News'], $rows[0]['tags']);
        $this->assertSame('page', $rows[1]['type']);
        $this->assertSame('about-us', $rows[1]['slug']);
    }
}
