import { describe, expect, it } from 'vitest';
import { facetMatchesSelection, normalizeTagFacets } from './blogTagFacets';

describe('blogTagFacets', () => {
  const betaFacet = {
    key: 'beta',
    label: 'beta',
    count: 3,
    aliases: ['beta35', 'beta36', 'beta'],
  };

  it('matches facet key and aliases', () => {
    expect(facetMatchesSelection(betaFacet, 'beta')).toBe(true);
    expect(facetMatchesSelection(betaFacet, 'beta35')).toBe(true);
    expect(facetMatchesSelection(betaFacet, 'security')).toBe(false);
  });

  it('falls back from legacy tag list', () => {
    const facets = normalizeTagFacets(undefined, ['news', 'security']);
    expect(facets).toHaveLength(2);
    expect(facets[0]?.key).toBe('news');
  });
});
