<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Editor;

use PaginiumCMS\Core\Editor\Services\ProseFigureMarkup;
use PHPUnit\Framework\TestCase;

final class ProseFigureMarkupTest extends TestCase
{
    public function testValidateAllowsLibraryFigure(): void
    {
        $markup = new ProseFigureMarkup();
        $content = <<<MD
Text

<figure class="paginium-figure">
<img src="/storage/app/content/media/a.png" alt="Alt" class="max-w-full h-auto rounded-lg" />
<figcaption>Obr. 1.1</figcaption>
</figure>
MD;

        $this->assertNull($markup->validateBlocks($content));
    }

    public function testValidateRejectsExternalImageSrc(): void
    {
        $markup = new ProseFigureMarkup();
        $content = '<figure class="paginium-figure"><img src="https://evil.test/x.png" alt="" /><figcaption>x</figcaption></figure>';

        $this->assertNotNull($markup->validateBlocks($content));
    }
}
