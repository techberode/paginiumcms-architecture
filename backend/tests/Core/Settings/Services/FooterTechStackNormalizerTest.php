<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Settings\Services;

use PaginiumCMS\Core\Settings\Services\FooterTechStackNormalizer;
use PHPUnit\Framework\TestCase;

final class FooterTechStackNormalizerTest extends TestCase
{
    public function testDefaultsUsedWhenJsonEmpty(): void
    {
        $items = FooterTechStackNormalizer::publicItems('', true);
        self::assertNotEmpty($items);
        self::assertSame('PHP 8.5', $items[0]['label']);
    }

    public function testMasterDisabledReturnsEmpty(): void
    {
        $json = FooterTechStackNormalizer::encode(FooterTechStackNormalizer::defaults());
        self::assertSame([], FooterTechStackNormalizer::publicItems($json, false));
    }

    public function testNormalizeAndEncode(): void
    {
        $json = FooterTechStackNormalizer::encode([
            [
                'id' => 'react',
                'label' => 'React',
                'url' => 'https://react.dev/',
                'icon' => 'react',
                'enabled' => true,
            ],
        ]);
        $public = FooterTechStackNormalizer::publicItems($json, true);
        self::assertCount(1, $public);
        self::assertSame('https://react.dev/', $public[0]['url']);
    }
}
