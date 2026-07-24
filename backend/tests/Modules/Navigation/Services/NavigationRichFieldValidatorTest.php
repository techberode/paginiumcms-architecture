<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Navigation\Services;

use PaginiumCMS\Core\FlatFile\Models\NavigationItem;
use PaginiumCMS\Modules\Navigation\Services\NavigationRichFieldValidator;
use PHPUnit\Framework\TestCase;

final class NavigationRichFieldValidatorTest extends TestCase
{
    private NavigationRichFieldValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new NavigationRichFieldValidator();
    }

    public function testAcceptsRichFields(): void
    {
        $error = $this->validator->validateEntry([
            'label' => 'Blog',
            'path' => '/blog',
            'description' => 'Novinky a tipy',
            'iconType' => 'media',
            'iconValue' => '/media/blog-icon.png',
            'previewOnHover' => true,
            'previewScale' => 2.0,
            'thumbnailSize' => 'md',
        ]);

        $this->assertNull($error);
    }

    public function testRejectsLongDescription(): void
    {
        $error = $this->validator->validateEntry([
            'label' => 'X',
            'path' => '/x',
            'description' => str_repeat('a', NavigationItem::MAX_DESCRIPTION_LENGTH + 1),
        ]);

        $this->assertNotNull($error);
    }

    public function testRejectsInvalidMediaPath(): void
    {
        $error = $this->validator->validateEntry([
            'label' => 'X',
            'path' => '/x',
            'iconType' => 'media',
            'iconValue' => '/etc/passwd',
        ]);

        $this->assertNotNull($error);
    }
}
