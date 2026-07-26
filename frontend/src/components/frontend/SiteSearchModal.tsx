// frontend/src/components/frontend/SiteSearchModal.tsx
import React, { useEffect, useMemo, useState } from 'react';
import { Search, FileText, BookOpen, Calendar, Tag, ChevronRight, X } from 'lucide-react';
import { usePublicSite } from '../../context/PublicSiteContext';
import { useI18n } from '../../context/I18nContext';
import { formatDisplayDate, resolveContentDate } from '../../utils/contentDates';
import { searchContent, SearchResultItem } from '../../api/search';
import { Article, Page } from '../../api/types';

interface SiteSearchModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSelectRoute: (path: string) => void;
}

interface SearchResult {
  id: string;
  title: string;
  path: string;
  type: 'page' | 'article';
  date?: string | number;
  tags?: string[];
  matchSnippet: string;
}

function pageSnippet(page: Page, q: string): string {
  const desc = String(page.frontMatter?.description ?? '');
  if (desc.toLowerCase().includes(q)) {
    return desc;
  }
  const content = page.content.toLowerCase();
  const idx = content.indexOf(q);
  if (idx >= 0) {
    return `...${page.content.substring(Math.max(0, idx - 40), Math.min(page.content.length, idx + 80)).replace(/\n/g, ' ')}...`;
  }
  return desc || page.title;
}

function articleSnippet(article: Article, q: string): string {
  const desc = article.excerpt || String(article.frontMatter?.description ?? '');
  if (desc.toLowerCase().includes(q)) {
    return desc;
  }
  const content = article.content.toLowerCase();
  const idx = content.indexOf(q);
  if (idx >= 0) {
    return `...${article.content.substring(Math.max(0, idx - 40), Math.min(article.content.length, idx + 80)).replace(/\n/g, ' ')}...`;
  }
  return desc || article.title;
}

export const SiteSearchModal: React.FC<SiteSearchModalProps> = ({ isOpen, onClose, onSelectRoute }) => {
  const { t, locale } = useI18n();
  const { pages, articles } = usePublicSite();
  const [query, setQuery] = useState('');
  const [apiResults, setApiResults] = useState<SearchResultItem[]>([]);
  const [searchLoading, setSearchLoading] = useState(false);

  useEffect(() => {
    if (!query.trim() || query.length < 2) {
      setApiResults([]);
      return;
    }

    let cancelled = false;
    const timer = window.setTimeout(async () => {
      setSearchLoading(true);
      try {
        const results = await searchContent(query, { limit: 30 });
        if (!cancelled) {
          setApiResults(results);
        }
      } finally {
        if (!cancelled) {
          setSearchLoading(false);
        }
      }
    }, 250);

    return () => {
      cancelled = true;
      window.clearTimeout(timer);
    };
  }, [query]);

  const clientResults = useMemo(() => {
    if (!query.trim() || query.length < 2) {
      return [];
    }

    const q = query.toLowerCase();
    const results: SearchResult[] = [];

    pages.forEach((page) => {
      const title = page.title.toLowerCase();
      const desc = String(page.frontMatter?.description ?? '').toLowerCase();
      const content = page.content.toLowerCase();
      if (title.includes(q) || desc.includes(q) || content.includes(q)) {
        const path = page.slug === 'home' ? '/' : `/${page.slug}`;
        results.push({
          id: page.id,
          title: page.title,
          path,
          type: 'page',
          date: resolveContentDate(page.frontMatter?.date, page.createdAt),
          matchSnippet: pageSnippet(page, q),
        });
      }
    });

    articles.forEach((article) => {
      const title = article.title.toLowerCase();
      const desc = (article.excerpt || String(article.frontMatter?.description ?? '')).toLowerCase();
      const content = article.content.toLowerCase();
      const tagMatch = article.tags?.some((t) => t.toLowerCase().includes(q));
      if (title.includes(q) || desc.includes(q) || content.includes(q) || tagMatch) {
        results.push({
          id: article.id,
          title: article.title,
          path: `/blog/${article.slug}`,
          type: 'article',
          date: resolveContentDate(article.frontMatter?.date, article.createdAt),
          tags: article.tags,
          matchSnippet: articleSnippet(article, q),
        });
      }
    });

    return results;
  }, [query, pages, articles]);

  const searchResults: SearchResult[] = useMemo(() => {
    if (query.trim().length >= 2 && apiResults.length > 0) {
      return apiResults.map((item) => ({
        id: `${item.type}:${item.slug}`,
        title: item.title,
        path: item.type === 'article' ? `/blog/${item.slug}` : (item.slug === 'home' ? '/' : `/${item.slug}`),
        type: item.type,
        date: item.updatedAt,
        tags: item.tags,
        matchSnippet: item.excerpt || item.title,
      }));
    }

    return clientResults;
  }, [apiResults, clientResults, query]);

  if (!isOpen) {
    return null;
  }

  return (
    <div
      className="fixed inset-0 z-50 flex items-start justify-center pt-16 sm:pt-24 px-4 bg-theme-text/80 backdrop-blur-sm animate-fadeIn"
      onClick={onClose}
      role="presentation"
    >
      <div
        className="bg-theme-surface-elevated rounded-2xl shadow-2xl max-w-2xl w-full border border-theme-border overflow-hidden"
        onClick={(e) => e.stopPropagation()}
        role="dialog"
        aria-modal="true"
      >
        <div className="flex items-center px-5 py-4 border-b border-theme-border gap-3">
          <Search className="w-5 h-5 text-theme-primary" />
          <input
            type="text"
            autoFocus
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder={t('public.search.placeholder')}
            className="flex-1 bg-transparent text-theme-text placeholder-theme-text-muted focus:outline-none text-base sm:text-lg"
          />
          <button
            type="button"
            onClick={onClose}
            className="p-1 text-theme-text-muted hover:text-theme-text rounded-lg hover:bg-theme-surface transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        <div className="max-h-[60vh] overflow-y-auto p-3 divide-y divide-theme-border/60">
          {searchResults.length === 0 && query.length >= 2 && !searchLoading && (
            <div className="py-12 text-center text-theme-text-muted">
              <p className="text-base font-medium">{t('public.search.noResults', { query })}</p>
            </div>
          )}

          {searchResults.length === 0 && query.length < 2 && (
            <div className="py-8 text-center text-theme-text-muted text-xs sm:text-sm">
              {t('public.search.minCharsHint')}
            </div>
          )}

          {searchResults.map((item) => (
            <button
              key={item.id}
              type="button"
              onClick={() => {
                onSelectRoute(item.path);
                onClose();
              }}
              className="w-full text-left p-4 hover:bg-theme-surface rounded-xl cursor-pointer transition-colors flex items-start justify-between group"
            >
              <div className="flex-1 pr-4">
                <div className="flex items-center gap-2 mb-1">
                  <span
                    className={`text-[10px] font-bold px-2 py-0.5 rounded-md flex items-center gap-1 ${
                      item.type === 'page'
                        ? 'bg-theme-primary/10 text-theme-primary'
                        : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400'
                    }`}
                  >
                    {item.type === 'page' ? <FileText className="w-2.5 h-2.5" /> : <BookOpen className="w-2.5 h-2.5" />}
                    {item.type === 'page' ? t('public.search.typePage') : t('public.search.typeArticle')}
                  </span>
                  <span className="text-xs text-theme-text-muted flex items-center gap-1">
                    <Calendar className="w-3 h-3" />
                    {formatDisplayDate(item.date, locale)}
                  </span>
                </div>
                <h4 className="text-base font-semibold text-theme-text group-hover:text-theme-primary transition-colors">
                  {item.title}
                </h4>
                <p className="text-xs text-theme-text-muted mt-1 line-clamp-2">{item.matchSnippet}</p>
                {item.tags && item.tags.length > 0 && (
                  <div className="flex flex-wrap gap-1 mt-2">
                    {item.tags.map((t) => (
                      <span
                        key={t}
                        className="text-[10px] bg-theme-surface text-theme-text-muted px-1.5 py-0.5 rounded flex items-center gap-0.5"
                      >
                        <Tag className="w-2.5 h-2.5" />
                        {t}
                      </span>
                    ))}
                  </div>
                )}
              </div>
              <ChevronRight className="w-5 h-5 text-theme-border group-hover:text-theme-primary group-hover:translate-x-1 transition-all self-center" />
            </button>
          ))}
        </div>

        <div className="bg-theme-surface px-5 py-3 border-t border-theme-border text-[11px] text-theme-text-muted flex items-center justify-between">
          <span>{t('public.search.footerSource')}</span>
          <span className="font-semibold text-theme-primary">
            {searchLoading
              ? t('public.search.searching')
              : t('public.search.resultCount', { count: searchResults.length })}
          </span>
        </div>
      </div>
    </div>
  );
};

export default SiteSearchModal;
