// src/api/content.ts
import api from './client';
import type { Page, Article, Content } from './types';

export const contentApi = {
  // Stránky
  async getPages(): Promise<Page[]> {
    const response = await api.get<{ pages: Page[] }>('/api/content/pages');
    return response.pages;
  },

  async getPage(slug: string): Promise<Page | null> {
    try {
      const response = await api.get<{ page: Page }>(`/api/content/pages/${slug}`);
      return response.page;
    } catch {
      return null;
    }
  },

  async createPage(data: Partial<Page>): Promise<Page> {
    const response = await api.post<{ page: Page }>('/api/content/pages', data);
    return response.page;
  },

  async updatePage(slug: string, data: Partial<Page>): Promise<Page> {
    const response = await api.put<{ page: Page }>(`/api/content/pages/${slug}`, data);
    return response.page;
  },

  async deletePage(slug: string): Promise<void> {
    await api.delete(`/api/content/pages/${slug}`);
  },

  // Články
  async getArticles(): Promise<Article[]> {
    const response = await api.get<{ articles: Article[] }>('/api/content/articles');
    return response.articles;
  },

  async getArticle(slug: string): Promise<Article | null> {
    try {
      const response = await api.get<{ article: Article }>(`/api/content/articles/${slug}`);
      return response.article;
    } catch {
      return null;
    }
  },

  async createArticle(data: Partial<Article>): Promise<Article> {
    const response = await api.post<{ article: Article }>('/api/content/articles', data);
    return response.article;
  },

  async updateArticle(slug: string, data: Partial<Article>): Promise<Article> {
    const response = await api.put<{ article: Article }>(`/api/content/articles/${slug}`, data);
    return response.article;
  },

  async deleteArticle(slug: string): Promise<void> {
    await api.delete(`/api/content/articles/${slug}`);
  },

  // Obsah všeobecne
  async getContent(path: string): Promise<Content | null> {
    try {
      const response = await api.get<{ content: Content }>(`/api/content/${path}`);
      return response.content;
    } catch {
      return null;
    }
  },

  async saveContent(path: string, data: Partial<Content>): Promise<Content> {
    const response = await api.put<{ content: Content }>(`/api/content/${path}`, data);
    return response.content;
  },

  async deleteContent(path: string): Promise<void> {
    await api.delete(`/api/content/${path}`);
  },
};
