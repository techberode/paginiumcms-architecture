<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Support;

use PaginiumCMS\Support\Lang;
use PHPUnit\Framework\TestCase;

final class LangTest extends TestCase
{
    protected function tearDown(): void
    {
        Lang::resetForTests();
        parent::tearDown();
    }

    public function testDefaultLocaleIsSlovak(): void
    {
        $this->assertSame('sk', Lang::getLocale());
        $this->assertSame('Obsah nebol nájdený', Lang::get('not_found', [], 'content'));
    }

    public function testEnglishLocaleLoadsModuleFile(): void
    {
        Lang::setLocale('en');
        $this->assertSame('Content not found', Lang::get('not_found', [], 'content'));
    }

    public function testPlaceholderReplacement(): void
    {
        $this->assertSame(
            'Obsah so slugom demo už existuje',
            Lang::get('slug_exists', ['slug' => 'demo'], 'content')
        );
    }

    public function testUnknownKeyReturnsKeyName(): void
    {
        $this->assertSame('missing_key', Lang::get('missing_key', [], 'content'));
    }
}
