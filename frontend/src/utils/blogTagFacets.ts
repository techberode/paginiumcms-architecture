import type { BlogTagFacet } from '../api/blogSidebar';

export function facetMatchesSelection(facet: BlogTagFacet, selectedTag: string | null): boolean {
  if (selectedTag === null || selectedTag === '') {
    return false;
  }

  const needle = selectedTag.toLowerCase();
  if (facet.key.toLowerCase() === needle) {
    return true;
  }

  return facet.aliases.some((alias) => alias.toLowerCase() === needle);
}

export function resolveTagFacetLabel(facets: BlogTagFacet[], selectedTag: string | null): string | null {
  if (!selectedTag) {
    return null;
  }

  for (const facet of facets) {
    if (facetMatchesSelection(facet, selectedTag)) {
      return facet.label;
    }
  }

  return selectedTag;
}

export function normalizeTagFacets(
  tagFacets: BlogTagFacet[] | undefined,
  legacyTags: string[] | undefined
): BlogTagFacet[] {
  if (tagFacets && tagFacets.length > 0) {
    return tagFacets;
  }

  return (legacyTags ?? []).map((tag) => ({
    key: tag,
    label: tag,
    count: 0,
    aliases: [tag],
  }));
}
