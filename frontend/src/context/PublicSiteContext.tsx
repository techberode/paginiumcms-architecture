// frontend/src/context/PublicSiteContext.tsx
import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import apiClient from '../api/client';
import { getNavigation } from '../api/navigation';
import { Article, Page } from '../api/types';
import { useSettingsContext } from './SettingsContext';
import { debugLogProvider } from '../utils/debugLog';
import { normalizeLocale, translate } from '../i18n';

import { buildNavigationTree, mapNavigationTreeToPublic } from '../utils/navigationTree';
import type { NavigationItem } from '../api/navigation';

export interface PublicNavItem {
  id: string;
  label: string;
  path: string;
  order: number;
  parentId?: string | null;
  children?: PublicNavItem[];
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

const CORE_NAV_IDS = [
  { id: 'home', key: 'public.nav.home', path: '/', order: 1 },
  { id: 'blog', key: 'public.nav.blog', path: '/blog', order: 2 },
] as const;

function buildCoreNav(locale: ReturnType<typeof normalizeLocale>): PublicNavItem[] {
  return CORE_NAV_IDS.map((item) => ({
    id: item.id,
    label: translate(locale, item.key),
    path: item.path,
    order: item.order,
  }));
}

function buildNavigation(pages: Page[], locale: ReturnType<typeof normalizeLocale>): PublicNavItem[] {
  const items: PublicNavItem[] = buildCoreNav(locale);
  const reserved = new Set(['home', 'index', 'blog']);
  const localeTag = locale === 'en' ? 'en' : 'sk';

  pages
    .filter((page) => !reserved.has(page.slug))
    .sort((a, b) => a.title.localeCompare(b.title, localeTag))
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
  const [navigationItems, setNavigationItems] = useState<NavigationItem[]>([]);
  const [loading, setLoading] = useState(true);

  const refresh = useCallback(async () => {
    setLoading(true);
    debugLogProvider('publicSite', 'refresh.start');
    try {
      const [pagesRes, navItems] = await Promise.all([
        apiClient.get<Page[]>('/api/pages'),
        getNavigation(),
      ]);
      const publishedPages = pagesRes.success ? (pagesRes.data || []).filter((p) => p.status === 'published') : [];
      if (pagesRes.success) {
        setPages(publishedPages);
      }
      setArticles([]);
      if (navItems.length > 0) {
        setNavigationItems(navItems);
      } else {
        setNavigationItems([]);
      }
      debugLogProvider('publicSite', 'refresh.done', {
        pages: publishedPages.length,
        articles: 0,
        nav: navItems.length,
        pagesOk: pagesRes.success,
        articlesOk: true,
      });
    } catch (error) {
      debugLogProvider('publicSite', 'refresh.error', {
        message: error instanceof Error ? error.message : 'unknown',
      });
    } finally {
      setLoading(false);
    }
  }, [settings.general?.language]);

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
  const locale = normalizeLocale(general?.language);
  const siteName = String(general?.siteName ?? translate(locale, 'public.defaults.siteName'));

  const navigation = useMemo(() => {
    if (navigationItems.length > 0) {
      return mapNavigationTreeToPublic(buildNavigationTree(navigationItems));
    }
    return buildNavigation(pages, locale).map((item) => ({ ...item, children: [] }));
  }, [navigationItems, pages, locale]);

  const value = useMemo(
    () => ({
      pages,
      articles,
      loading,
      navigation,
      siteTitle: siteName,
      siteTagline: String(general?.siteDescription ?? translate(locale, 'public.defaults.siteTagline')),
      footerText: translate(locale, 'public.footer.copyright', {
        year: new Date().getFullYear(),
        siteName,
      }),
      refresh,
      getPageBySlug,
      getArticleBySlug,
    }),
    [pages, articles, loading, navigation, general, locale, siteName, refresh, getPageBySlug, getArticleBySlug]
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
