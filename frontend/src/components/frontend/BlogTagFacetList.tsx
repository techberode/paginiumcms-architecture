import React, { useMemo, useState } from 'react';
import { Search, Tag } from 'lucide-react';
import type { BlogTagFacet } from '../../api/blogSidebar';
import { useI18n } from '../../context/I18nContext';
import { BTN_PRIMARY, INPUT_THEME } from '../../theme/publicUiClasses';
import { facetMatchesSelection } from '../../utils/blogTagFacets';

interface BlogTagFacetListProps {
  facets: BlogTagFacet[];
  selectedTag: string | null;
  limit: number;
  onSelectTag: (tag: string | null) => void;
  variant?: 'chips' | 'sidebar';
}

export const BlogTagFacetList: React.FC<BlogTagFacetListProps> = ({
  facets,
  selectedTag,
  limit,
  onSelectTag,
  variant = 'sidebar',
}) => {
  const { t } = useI18n();
  const [query, setQuery] = useState('');
  const [expanded, setExpanded] = useState(false);

  const filtered = useMemo(() => {
    const needle = query.trim().toLowerCase();
    if (needle === '') {
      return facets;
    }

    return facets.filter(
      (facet) =>
        facet.label.toLowerCase().includes(needle) ||
        facet.key.toLowerCase().includes(needle) ||
        facet.aliases.some((alias) => alias.toLowerCase().includes(needle))
    );
  }, [facets, query]);

  const visible = expanded || query.trim() !== '' ? filtered : filtered.slice(0, limit);
  const hiddenCount = Math.max(0, filtered.length - limit);

  const chipClass = (active: boolean) =>
    variant === 'chips'
      ? `px-4 py-2 rounded-xl text-xs font-extrabold transition-all cursor-pointer flex items-center gap-1.5 ${
          active ? `${BTN_PRIMARY} shadow-md` : 'bg-theme-surface hover:bg-theme-surface-elevated text-theme-text-muted'
        }`
      : `pg-blog-tag-chip ${active ? 'is-active' : ''}`;

  return (
    <div className={variant === 'sidebar' ? 'pg-blog-tag-list' : 'flex flex-col gap-3'}>
      {facets.length > limit ? (
        <label className="sr-only" htmlFor="blog-tag-facet-search">
          {t('public.blog.tags.searchLabel')}
        </label>
      ) : null}
      {facets.length > 8 ? (
        <div className={variant === 'sidebar' ? 'mb-2' : 'w-full max-w-md mx-auto'}>
          <div className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-theme-text-muted pointer-events-none" />
            <input
              id="blog-tag-facet-search"
              type="search"
              value={query}
              onChange={(event) => setQuery(event.target.value)}
              placeholder={t('public.blog.tags.searchPlaceholder')}
              className={`w-full pl-9 pr-3 py-2 text-sm rounded-xl ${INPUT_THEME}`}
              autoComplete="off"
            />
          </div>
        </div>
      ) : null}

      <div className={variant === 'chips' ? 'flex flex-wrap justify-center gap-2' : undefined}>
        {visible.map((facet) => {
          const active = facetMatchesSelection(facet, selectedTag);
          return (
            <button
              key={facet.key}
              type="button"
              title={
                facet.aliases.length > 1
                  ? t('public.blog.tags.groupHint', { aliases: facet.aliases.join(', ') })
                  : undefined
              }
              onClick={() => onSelectTag(active ? null : facet.key)}
              className={chipClass(active)}
            >
              {variant === 'chips' ? <Tag className="w-3 h-3" /> : null}
              <span>{facet.label}</span>
              {facet.count > 0 ? (
                <span className={variant === 'chips' ? 'opacity-80' : 'pg-blog-tag-count'}>{facet.count}</span>
              ) : null}
            </button>
          );
        })}
      </div>

      {!expanded && query.trim() === '' && hiddenCount > 0 ? (
        <button
          type="button"
          className="pg-blog-tag-more text-xs font-bold text-theme-primary mt-2"
          onClick={() => setExpanded(true)}
        >
          {t('public.blog.tags.showMore', { count: hiddenCount })}
        </button>
      ) : null}
    </div>
  );
};
