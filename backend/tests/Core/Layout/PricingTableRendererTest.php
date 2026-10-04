<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Layout;

use PaginiumCMS\Core\Layout\Services\PricingTableRenderer;
use PHPUnit\Framework\TestCase;

final class PricingTableRendererTest extends TestCase
{
    public function testStaticWrapperWithoutToggle(): void
    {
        $html = PricingTableRenderer::render(['columns' => '2'], '<article class="pg-plan">x</article>');
        $this->assertStringContainsString('pg-pricing-cols-2', $html);
        $this->assertStringNotContainsString('pg-island', $html);
        $this->assertStringContainsString('<article class="pg-plan">x</article>', $html);
    }

    public function testBillingToggleEmitsIslandShellWithInner(): void
    {
        $html = PricingTableRenderer::render(
            [
                'billing-toggle' => 'monthly-yearly',
                'label-monthly' => 'Mesiac',
                'label-yearly' => 'Rok',
            ],
            '<article class="pg-plan">plan</article>'
        );

        $this->assertStringContainsString('pg-island--pricing-table', $html);
        $this->assertStringContainsString('data-label-monthly="Mesiac"', $html);
        $this->assertStringContainsString('<article class="pg-plan">plan</article>', $html);
    }
}
