<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Seo\Services;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\Seo\Services\NotFoundHitStore;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

final class NotFoundHitStoreTest extends TestCase
{
    private NotFoundHitStore $store;

    protected function setUp(): void
    {
        vfsStream::setup('root', null, ['data' => ['metrics' => []]]);
        $root = vfsStream::url('root');
        $validator = new FileValidator($root);
        $reader = new FileReader($validator);
        $this->store = new NotFoundHitStore($reader);
    }

    public function testRecordAndAggregateTopPaths(): void
    {
        $this->store->record('/missing-page', 'https://example.com/blog', 'Mozilla/5.0');
        $this->store->record('/missing-page', null, 'Mozilla/5.0');
        $this->store->record('/other', 'https://google.com/search', 'Bot');

        $top = $this->store->topPaths(7, 10);

        $this->assertCount(2, $top);
        $this->assertSame('/missing-page', $top[0]['path']);
        $this->assertSame(2, $top[0]['hits']);
        $this->assertSame('example.com', $top[0]['topReferer']);
    }

    public function testExportCsvSanitizesCells(): void
    {
        $this->store->record('/weird,path"', null, null);

        $csv = $this->store->exportCsv(7);

        $this->assertStringContainsString('path,hits,last_seen,top_referer', $csv);
        $this->assertStringContainsString('"/weird,path""', $csv);
    }

    public function testRejectsInvalidPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->store->normalizePath('/../etc/passwd');
    }
}
