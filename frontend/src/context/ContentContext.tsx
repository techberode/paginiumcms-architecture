// frontend/src/context/ContentContext.tsx
import React, { createContext, useContext, useState, useCallback } from 'react';
import { useApi } from '../hooks/useApi';
import { Page, Article } from '../api/types';

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
    try {
      const response = await get<Page[]>('/api/pages');
      if (response.success) {
        setPages(response.data || []);
      }
    } catch (error) {
      console.error('Failed to load pages:', error);
    }
  }, [get]);

  const loadArticles = useCallback(async () => {
    try {
      const response = await get<Article[]>('/api/articles');
      if (response.success) {
        setArticles(response.data || []);
      }
    } catch (error) {
      console.error('Failed to load articles:', error);
    }
  }, [get]);

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      await Promise.all([loadPages(), loadArticles()]);
    } finally {
      setLoading(false);
    }
  }, [loadPages, loadArticles]);

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
