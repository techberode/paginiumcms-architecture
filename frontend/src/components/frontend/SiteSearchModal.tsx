// frontend/src/components/frontend/SiteSearchModal.tsx
import React, { useEffect, useMemo, useState } from 'react';
import { Search, FileText, BookOpen, Calendar, Tag, ChevronRight, X } from 'lucide-react';
import { usePublicSite } from '../../context/PublicSiteContext';
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
  date: string;
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
          date: String(page.frontMatter?.date ?? page.createdAt),
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
          date: String(article.frontMatter?.date ?? article.createdAt),
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
      className="fixed inset-0 z-50 flex items-start justify-center pt-16 sm:pt-24 px-4 bg-slate-900/80 backdrop-blur-sm animate-fadeIn"
      onClick={onClose}
      role="presentation"
    >
      <div
        className="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl max-w-2xl w-full border border-slate-200 dark:border-slate-800 overflow-hidden"
        onClick={(e) => e.stopPropagation()}
        role="dialog"
        aria-modal="true"
      >
        <div className="flex items-center px-5 py-4 border-b border-slate-100 dark:border-slate-800 gap-3">
          <Search className="w-5 h-5 text-indigo-500" />
          <input
            type="text"
            autoFocus
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder="Hľadať v článkoch, podstránkach alebo tagoch (min. 2 znaky)..."
            className="flex-1 bg-transparent text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none text-base sm:text-lg"
          />
          <button
            type="button"
            onClick={onClose}
            className="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        <div className="max-h-[60vh] overflow-y-auto p-3 divide-y divide-slate-100 dark:divide-slate-800/60">
          {searchResults.length === 0 && query.length >= 2 && !searchLoading && (
            <div className="py-12 text-center text-slate-500 dark:text-slate-400">
              <p className="text-base font-medium">Nenašli sa žiadne FlatFile záznamy pre &quot;{query}&quot;</p>
            </div>
          )}

          {searchResults.length === 0 && query.length < 2 && (
            <div className="py-8 text-center text-slate-400 dark:text-slate-500 text-xs sm:text-sm">
              Zadajte aspoň 2 znaky pre okamžité FlatFile vyhľadávanie.
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
              className="w-full text-left p-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 rounded-xl cursor-pointer transition-colors flex items-start justify-between group"
            >
              <div className="flex-1 pr-4">
                <div className="flex items-center gap-2 mb-1">
                  <span
                    className={`text-[10px] font-bold px-2 py-0.5 rounded-md flex items-center gap-1 ${
                      item.type === 'page'
                        ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400'
                        : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400'
                    }`}
                  >
                    {item.type === 'page' ? <FileText className="w-2.5 h-2.5" /> : <BookOpen className="w-2.5 h-2.5" />}
                    {item.type === 'page' ? 'Stránka' : 'Blog'}
                  </span>
                  <span className="text-xs text-slate-400 flex items-center gap-1">
                    <Calendar className="w-3 h-3" />
                    {new Date(item.date).toLocaleDateString('sk-SK')}
                  </span>
                </div>
                <h4 className="text-base font-semibold text-slate-900 dark:text-slate-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                  {item.title}
                </h4>
                <p className="text-xs text-slate-600 dark:text-slate-300 mt-1 line-clamp-2">{item.matchSnippet}</p>
                {item.tags && item.tags.length > 0 && (
                  <div className="flex flex-wrap gap-1 mt-2">
                    {item.tags.map((t) => (
                      <span
                        key={t}
                        className="text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 px-1.5 py-0.5 rounded flex items-center gap-0.5"
                      >
                        <Tag className="w-2.5 h-2.5" />
                        {t}
                      </span>
                    ))}
                  </div>
                )}
              </div>
              <ChevronRight className="w-5 h-5 text-slate-300 dark:text-slate-600 group-hover:text-indigo-500 group-hover:translate-x-1 transition-all self-center" />
            </button>
          ))}
        </div>

        <div className="bg-slate-50 dark:bg-slate-800/80 px-5 py-3 border-t border-slate-100 dark:border-slate-800 text-[11px] text-slate-500 dark:text-slate-400 flex items-center justify-between">
          <span>Výsledky z publikovaného FlatFile obsahu</span>
          <span className="font-semibold text-indigo-600 dark:text-indigo-400">
            {searchLoading ? 'Hľadám…' : `${searchResults.length} záznamov`}
          </span>
        </div>
      </div>
    </div>
  );
};

export default SiteSearchModal;
