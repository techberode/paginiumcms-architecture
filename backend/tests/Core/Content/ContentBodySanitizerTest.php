<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Content;

use PaginiumCMS\Core\Content\ContentBodySanitizer;
use PHPUnit\Framework\TestCase;

final class ContentBodySanitizerTest extends TestCase
{
    public function testDetectsAndStripsEmbeddedYamlMetadataBlock(): void
    {
        $body = <<<'MD'
# Article intro

Some paragraph.

```bash
php console redirect:validate
```
seo:
  title: beta38
  description: 'PaginiumCMS Beta38'
localeStatus:
  sk: published
slug: demo title: Demo
MD;

        $this->assertTrue(ContentBodySanitizer::looksLikeMetadataLeak($body));
        $clean = ContentBodySanitizer::stripEmbeddedMetadataLeak($body);
        $this->assertStringContainsString('redirect:validate', $clean);
        $this->assertStringNotContainsString('localeStatus:', $clean);
        $this->assertStringNotContainsString("\nseo:\n", $clean);
    }

    public function testLeavesNormalMarkdownUntouched(): void
    {
        $body = "# Title\n\nParagraph with **bold** and `code`.\n\n---\n\n## Section";

        $this->assertFalse(ContentBodySanitizer::looksLikeMetadataLeak($body));
        $this->assertSame($body, ContentBodySanitizer::stripEmbeddedMetadataLeak($body));
    }
}
