<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Layout;

use PaginiumCMS\Core\Layout\Services\PricingPlanRenderer;
use PHPUnit\Framework\TestCase;

final class PricingPlanRendererTest extends TestCase
{
    public function testDualBillingAmounts(): void
    {
        $html = PricingPlanRenderer::render(
            [
                'name' => 'Pro',
                'price-monthly' => '€10',
                'price-yearly' => '€100',
                'period-monthly' => '/mo',
                'period-yearly' => '/yr',
            ],
            '<li class="pg-plan-feature">x</li>'
        );

        $this->assertStringContainsString('pg-plan-amount--monthly', $html);
        $this->assertStringContainsString('pg-plan-amount--yearly', $html);
        $this->assertStringContainsString('€100', $html);
    }

    public function testLegacySinglePrice(): void
    {
        $html = PricingPlanRenderer::render(
            ['name' => 'Free', 'price' => '$0', 'period' => '/mo'],
            ''
        );

        $this->assertStringContainsString('pg-plan-amount">$0', $html);
        $this->assertStringNotContainsString('pg-plan-amount--yearly', $html);
    }
}
