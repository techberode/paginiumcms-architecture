// frontend/src/context/PublicSiteContext.tsx
import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import apiClient from '../api/client';
import { Article, Page } from '../api/types';
import { useSettingsContext } from './SettingsContext';
import { debugLogProvider } from '../utils/debugLog';

export interface PublicNavItem {
  id: string;
  label: string;
  path: string;
  order: number;
}

interface PublicSiteContextType {
  pages: Page[];
  articles: Article[];
  loading: boolean;
  navigation: PublicNavItem[];
  siteTitle: string;
  siteTagline: string;
  footerText: string;
  refresh: () => Promise<void>;
  getPageBySlug: (slug: string) => Page | undefined;
  getArticleBySlug: (slug: string) => Article | undefined;
}

const PublicSiteContext = createContext<PublicSiteContextType | undefined>(undefined);

const CORE_NAV: PublicNavItem[] = [
  { id: 'home', label: 'Domov', path: '/', order: 1 },
  { id: 'blog', label: 'Blog', path: '/blog', order: 2 },
];

function buildNavigation(pages: Page[]): PublicNavItem[] {
  const items: PublicNavItem[] = [...CORE_NAV];
  const reserved = new Set(['home', 'index', 'blog']);

  pages
    .filter((page) => !reserved.has(page.slug))
    .sort((a, b) => a.title.localeCompare(b.title, 'sk'))
    .forEach((page, index) => {
      items.push({
        id: page.id,
        label: page.title,
        path: `/${page.slug}`,
        order: 10 + index,
      });
    });

  return items;
}

export const PublicSiteProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const { settings } = useSettingsContext();
  const [pages, setPages] = useState<Page[]>([]);
  const [articles, setArticles] = useState<Article[]>([]);
  const [loading, setLoading] = useState(true);

  const refresh = useCallback(async () => {
    setLoading(true);
    debugLogProvider('publicSite', 'refresh.start');
    try {
      const [pagesRes, articlesRes] = await Promise.all([
        apiClient.get<Page[]>('/api/pages'),
        apiClient.get<Article[]>('/api/articles'),
      ]);
      const publishedPages = pagesRes.success ? (pagesRes.data || []).filter((p) => p.status === 'published') : [];
      const publishedArticles = articlesRes.success
        ? (articlesRes.data || []).filter((a) => a.status === 'published')
        : [];
      if (pagesRes.success) {
        setPages(publishedPages);
      }
      if (articlesRes.success) {
        setArticles(publishedArticles);
      }
      debugLogProvider('publicSite', 'refresh.done', {
        pages: publishedPages.length,
        articles: publishedArticles.length,
        pagesOk: pagesRes.success,
        articlesOk: articlesRes.success,
      });
    } catch (error) {
      debugLogProvider('publicSite', 'refresh.error', {
        message: error instanceof Error ? error.message : 'unknown',
      });
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  const getPageBySlug = useCallback(
    (slug: string) => pages.find((p) => p.slug === slug),
    [pages]
  );

  const getArticleBySlug = useCallback(
    (slug: string) => articles.find((a) => a.slug === slug),
    [articles]
  );

  const general = settings.general as Record<string, unknown>;
  const navigation = useMemo(() => buildNavigation(pages), [pages]);

  const value = useMemo(
    () => ({
      pages,
      articles,
      loading,
      navigation,
      siteTitle: String(general.siteName ?? 'PaginiumCMS'),
      siteTagline: String(general.siteDescription ?? 'FlatFile CMS'),
      footerText: `© ${new Date().getFullYear()} ${String(general.siteName ?? 'PaginiumCMS')}`,
      refresh,
      getPageBySlug,
      getArticleBySlug,
    }),
    [pages, articles, loading, navigation, general, refresh, getPageBySlug, getArticleBySlug]
  );

  return <PublicSiteContext.Provider value={value}>{children}</PublicSiteContext.Provider>;
};

export function usePublicSite(): PublicSiteContextType {
  const ctx = useContext(PublicSiteContext);
  if (!ctx) {
    throw new Error('usePublicSite must be used within PublicSiteProvider');
  }
  return ctx;
}
