<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\I18n;

use PaginiumCMS\Core\I18n\Services\TranslationMessageTreeParser;
use PHPUnit\Framework\TestCase;

final class TranslationMessageTreeParserTest extends TestCase
{
    private TranslationMessageTreeParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new TranslationMessageTreeParser();
    }

    public function testParsesTypeScriptMessageTreeExport(): void
    {
        $content = <<<'TS'
import type { MessageTree } from '../../types';

export const demoSk: MessageTree = {
  "page": {
    "title": "Nadpis",
    "save": "Uložiť",
  },
  "hint": "Text s {name} a :slug",
};
TS;

        $tree = $this->parser->parseTypeScriptCatalog($content);

        $this->assertSame('Nadpis', $tree['page']['title'] ?? null);
        $this->assertSame('Uložiť', $tree['page']['save'] ?? null);
        $this->assertSame('Text s {name} a :slug', $tree['hint'] ?? null);
    }

    public function testRejectsMissingExport(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->parser->parseTypeScriptCatalog('const x = { "a": "b" };');
    }
}
