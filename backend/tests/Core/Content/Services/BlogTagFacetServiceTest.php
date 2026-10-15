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

    public function testNumericTagsFromArrayKeysDoNotThrow(): void
    {
        // listDistinctTags() normalizes array_keys() values (e.g. int 404) to strings before calling here.
        $service = $this->serviceWithSettings(['blogTagFacetsEnabled' => true]);
        /** @var list<string> $raw */
        $raw = ['404', 'beta35', 'security'];
        $facets = $service->buildFacets($raw, ['beta35' => 1, 'security' => 2]);

        $this->assertContains('security', array_column($facets, 'key'));
        $this->assertNotEmpty($service->expandFilterNeedles('security', $raw));

        $disabled = $this->serviceWithSettings(['blogTagFacetsEnabled' => false]);
        $this->assertCount(3, $disabled->buildFacets($raw, []));
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
