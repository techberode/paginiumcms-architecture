<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Origin;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Origin\Services\OriginCatalogLabelResolver;
use PHPUnit\Framework\TestCase;

final class OriginCatalogLabelResolverTest extends TestCase
{
    public function testResolvesCatalogKeyFromBackendLang(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->with('general.language')->willReturn('sk');

        $resolver = new OriginCatalogLabelResolver($settings);

        $label = $resolver->resolve('origin.catalog.it87');

        $this->assertSame('It.87 Plánovač projektu + UX audit', $label);
    }

    public function testFallsBackToKeyWhenUnknown(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->with('general.language')->willReturn('en');

        $resolver = new OriginCatalogLabelResolver($settings);

        $this->assertSame('origin.catalog.unknown_key', $resolver->resolve('origin.catalog.unknown_key'));
    }

    public function testResolvesTimelineAndProbeKeys(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->with('general.language')->willReturn('en');

        $resolver = new OriginCatalogLabelResolver($settings);

        $this->assertSame(
            'Admin search fix, article print, bulk X-of-Y counter',
            $resolver->resolve('origin.timeline.it86')
        );
        $this->assertSame(
            'Bulk selected-of-total UX',
            $resolver->resolve('origin.probes.it86_bulk_selection')
        );
    }
}
