<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Media\Services;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Modules\Media\Services\StockImageCatalog;
use PHPUnit\Framework\TestCase;

class StockImageCatalogTest extends TestCase
{
    public function testTopicsFromBundledCatalog(): void
    {
        $catalog = new StockImageCatalog();
        $topics = $catalog->topics();

        $this->assertNotEmpty($topics);
        $ids = array_column($topics, 'id');
        $this->assertContains('tech', $ids);
        $this->assertContains('food', $ids);
    }

    public function testPickRandomFromTopic(): void
    {
        $catalog = new StockImageCatalog();
        $entry = $catalog->pickRandom('food');

        $this->assertStringStartsWith('https://images.unsplash.com/', $entry['url']);
        $this->assertNotSame('', $entry['fileName']);
        $this->assertSame('image/jpeg', $entry['mimeType']);
        $this->assertNotSame('', $entry['altText']);
    }

    public function testPickRandomFallsBackForUnknownTopic(): void
    {
        $catalog = new StockImageCatalog(__DIR__ . '/Fixtures/stock-images-test.json');
        $entry = $catalog->pickRandom('unknown-topic');

        $this->assertSame('general-sample.png', $entry['fileName']);
    }

    public function testInvalidCatalogThrows(): void
    {
        $this->expectException(FlatFileException::class);
        new StockImageCatalog('/tmp/missing-stock-catalog.json')->topics();
    }
}
