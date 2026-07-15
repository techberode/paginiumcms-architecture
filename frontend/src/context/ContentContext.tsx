// frontend/src/context/ContentContext.tsx
import React, { createContext, useContext, useState, useCallback } from 'react';
import { useApi } from '../hooks/useApi';
import { Page, Article } from '../api/types';
import { debugLogProvider } from '../utils/debugLog';

interface ContentContextType {
  pages: Page[];
  articles: Article[];
  loading: boolean;
  loadPages: () => Promise<void>;
  loadArticles: () => Promise<void>;
  getPage: (slug: string) => Page | undefined;
  getArticle: (slug: string) => Article | undefined;
  refresh: () => Promise<void>;
}

export const ContentContext = createContext<ContentContextType | undefined>(undefined);

export const ContentProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [pages, setPages] = useState<Page[]>([]);
  const [articles, setArticles] = useState<Article[]>([]);
  const [loading, setLoading] = useState(false);
  const { get } = useApi();

  const loadPages = useCallback(async () => {
    debugLogProvider('content', 'loadPages.start');
    try {
      const response = await get<Page[]>('/api/pages');
      if (response.success) {
        setPages(response.data || []);
        debugLogProvider('content', 'loadPages.done', { count: response.data?.length ?? 0 });
      } else {
        debugLogProvider('content', 'loadPages.failed', { error: response.error });
      }
    } catch (error) {
      debugLogProvider('content', 'loadPages.error', {
        message: error instanceof Error ? error.message : 'unknown',
      });
    }
  }, [get]);

  const loadArticles = useCallback(async () => {
    debugLogProvider('content', 'loadArticles.start');
    try {
      const response = await get<Article[]>('/api/articles');
      if (response.success) {
        setArticles(response.data || []);
        debugLogProvider('content', 'loadArticles.done', { count: response.data?.length ?? 0 });
      } else {
        debugLogProvider('content', 'loadArticles.failed', { error: response.error });
      }
    } catch (error) {
      debugLogProvider('content', 'loadArticles.error', {
        message: error instanceof Error ? error.message : 'unknown',
      });
    }
  }, [get]);

  const refresh = useCallback(async () => {
    setLoading(true);
    debugLogProvider('content', 'refresh.start');
    try {
      await Promise.all([loadPages(), loadArticles()]);
      debugLogProvider('content', 'refresh.done', {
        pages: pages.length,
        articles: articles.length,
      });
    } finally {
      setLoading(false);
    }
  }, [loadPages, loadArticles, pages.length, articles.length]);

  const getPage = useCallback((slug: string): Page | undefined => {
    return pages.find(p => p.slug === slug);
  }, [pages]);

  const getArticle = useCallback((slug: string): Article | undefined => {
    return articles.find(a => a.slug === slug);
  }, [articles]);

  return (
    <ContentContext.Provider
      value={{
        pages,
        articles,
        loading,
        loadPages,
        loadArticles,
        getPage,
        getArticle,
        refresh,
      }}
    >
      {children}
    </ContentContext.Provider>
  );
};

export const useContent = () => {
  const context = useContext(ContentContext);
  if (!context) {
    throw new Error('useContent must be used within a ContentProvider');
  }
  return context;
};

export default ContentProvider;
