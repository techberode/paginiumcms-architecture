<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Content\Services;

use PaginiumCMS\Core\Content\Services\BlogTagFacetService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class BlogTagFacetServiceTest extends TestCase
{
    public function testAutomaticGroupingCollapsesBetaVariants(): void
    {
        $service = $this->serviceWithSettings(['blogTagFacetsEnabled' => true]);
        $facets = $service->buildFacets(
            ['beta35', 'beta36', 'beta', 'security'],
            ['beta35' => 2, 'beta36' => 1, 'beta' => 3, 'security' => 4]
        );

        $keys = array_column($facets, 'key');
        $this->assertContains('beta', $keys);
        $this->assertContains('security', $keys);
        $this->assertCount(2, $facets);

        $beta = $facets[array_search('beta', $keys, true)];
        $this->assertContains('beta35', $beta['aliases']);
        $this->assertContains('beta36', $beta['aliases']);
    }

    public function testExpandFilterNeedlesReturnsAllAliases(): void
    {
        $service = $this->serviceWithSettings(['blogTagFacetsEnabled' => true]);
        $raw = ['beta35', 'beta36', 'security'];
        $needles = $service->expandFilterNeedles('beta', $raw);

        $this->assertContains('beta35', $needles);
        $this->assertContains('beta36', $needles);
        $this->assertNotContains('security', $needles);
    }

    public function testManualSynonymsMergeMediaVariants(): void
    {
        $service = $this->serviceWithSettings([
            'blogTagFacetsEnabled' => true,
            'blogTagFacetSynonyms' => '{"media":["média","médii","media"]}',
        ]);

        $facets = $service->buildFacets(
            ['média', 'médii', 'library'],
            ['média' => 1, 'médii' => 2, 'library' => 1]
        );

        $mediaFacet = null;
        foreach ($facets as $facet) {
            if ($facet['label'] === 'media') {
                $mediaFacet = $facet;
                break;
            }
        }

        $this->assertNotNull($mediaFacet);
        $this->assertContains('média', $mediaFacet['aliases']);
        $this->assertContains('médii', $mediaFacet['aliases']);
    }

    /**
     * @param array<string, mixed> $contentGroup
     */
    private function serviceWithSettings(array $contentGroup): BlogTagFacetService
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->with('content')->willReturn($contentGroup);

        return new BlogTagFacetService($settings);
    }
}
