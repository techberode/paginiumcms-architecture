<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\FlatFile\Services;

use PaginiumCMS\Core\FlatFile\Models\Page;
use PaginiumCMS\Core\FlatFile\Services\ContentRevision;
use PHPUnit\Framework\TestCase;

/**
 * Testy revízneho odtlačku obsahu (jadro detekcie konfliktov – Iterácia 2).
 */
class ContentRevisionTest extends TestCase
{
    private ContentRevision $revision;

    protected function setUp(): void
    {
        parent::setUp();
        $this->revision = new ContentRevision();
    }

    public function testRevisionIsDeterministic(): void
    {
        $a = $this->revision->compute('Ahoj svet', ['title' => 'Test', 'status' => 'draft']);
        $b = $this->revision->compute('Ahoj svet', ['title' => 'Test', 'status' => 'draft']);

        $this->assertSame($a, $b, 'Rovnaký obsah musí mať rovnakú revíziu');
        $this->assertSame(40, strlen($a), 'sha1 má 40 znakov');
    }

    public function testRevisionIsIndependentOfKeyOrder(): void
    {
        $a = $this->revision->compute('X', ['title' => 'T', 'status' => 'draft']);
        $b = $this->revision->compute('X', ['status' => 'draft', 'title' => 'T']);

        $this->assertSame($a, $b, 'Poradie kľúčov front matter nesmie ovplyvniť revíziu');
    }

    public function testDifferentContentProducesDifferentRevision(): void
    {
        $a = $this->revision->compute('Verzia A', ['title' => 'T']);
        $b = $this->revision->compute('Verzia B', ['title' => 'T']);

        $this->assertNotSame($a, $b);
    }

    public function testDifferentFrontMatterProducesDifferentRevision(): void
    {
        $a = $this->revision->compute('X', ['title' => 'Prvý']);
        $b = $this->revision->compute('X', ['title' => 'Druhý']);

        $this->assertNotSame($a, $b);
    }

    public function testMatchesReturnsTrueForEmptyBaseRevision(): void
    {
        $page = new Page();
        $page->setContent('obsah');

        $this->assertTrue($this->revision->matches($page, null), 'null base = bez kontroly');
        $this->assertTrue($this->revision->matches($page, ''), 'prázdny base = bez kontroly');
    }

    public function testMatchesDetectsChange(): void
    {
        $page = new Page();
        $page->setContent('pôvodný obsah');
        $original = $this->revision->forContent($page);

        // Rovnaký obsah => matches true
        $this->assertTrue($this->revision->matches($page, $original));

        // Zmena obsahu => matches false (konflikt)
        $page->setContent('zmenený obsah');
        $this->assertFalse($this->revision->matches($page, $original));
    }
}
