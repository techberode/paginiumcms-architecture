<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Import;

use org\bovigo\vfs\vfsStream;
use PaginiumCMS\Core\FlatFile\Services\FrontMatterParser;
use PaginiumCMS\Core\Import\GravPagesImporter;
use PaginiumCMS\Core\Import\MarkdownSiteImportScanner;
use PHPUnit\Framework\TestCase;

final class GravPagesImporterTest extends TestCase
{
    public function testParsesBlogItemAsArticle(): void
    {
        $root = vfsStream::setup('grav', null, [
            'user' => [
                'pages' => [
                    '02.blog' => [
                        '03.my-post' => [
                            'item.md' => <<<'MD'
---
title: My post
date: 2024-06-01 10:00:00
tags: [news, grav]
published: true
---
Hello **world**
MD,
                        ],
                    ],
                    '01.home' => [
                        'default.md' => <<<'MD'
---
title: Home
---
Welcome
MD,
                    ],
                ],
            ],
        ]);

        $importer = new GravPagesImporter(new MarkdownSiteImportScanner(new FrontMatterParser()));
        $rows = $importer->parseDirectory(vfsStream::url('grav/user/pages'));

        $this->assertCount(2, $rows);
        $article = $rows[0]['type'] === 'article' ? $rows[0] : $rows[1];
        $this->assertSame('article', $article['type']);
        $this->assertSame('grav', $article['importSource']);
        $this->assertSame('published', $article['status']);
        $this->assertContains('news', $article['tags']);
    }
}
