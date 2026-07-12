// frontend/src/api/content.ts
import apiClient from './client';
import { Page, Article, MediaFile } from './types';

export const contentApi = {
  // Stránky
  pages: {
    getAll: async (params?: { status?: string; author?: string }): Promise<Page[]> => {
      const response = await apiClient.get<Page[]>('/api/pages', { params });
      return response.data || [];
    },

    getBySlug: async (slug: string): Promise<Page> => {
      const response = await apiClient.get<Page>(`/api/pages/${slug}`);
      return response.data as Page;
    },

    create: async (data: Partial<Page>): Promise<Page> => {
      const response = await apiClient.post<Page>('/api/pages', data);
      return response.data as Page;
    },

    update: async (slug: string, data: Partial<Page>): Promise<Page> => {
      const response = await apiClient.put<Page>(`/api/pages/${slug}`, data);
      return response.data as Page;
    },

    delete: async (slug: string): Promise<{ success: boolean }> => {
      const response = await apiClient.delete(`/api/pages/${slug}`);
      return response.data as { success: boolean };
    },

    changeStatus: async (slug: string, status: 'draft' | 'published' | 'archived'): Promise<Page> => {
      const response = await apiClient.patch<Page>(`/api/pages/${slug}/status`, { status });
      return response.data as Page;
    },
  },

  // Články
  articles: {
    getAll: async (params?: { status?: string; author?: string; tag?: string }): Promise<Article[]> => {
      const response = await apiClient.get<Article[]>('/api/articles', { params });
      return response.data || [];
    },

    getBySlug: async (slug: string): Promise<Article> => {
      const response = await apiClient.get<Article>(`/api/articles/${slug}`);
      return response.data as Article;
    },

    create: async (data: Partial<Article>): Promise<Article> => {
      const response = await apiClient.post<Article>('/api/articles', data);
      return response.data as Article;
    },

    update: async (slug: string, data: Partial<Article>): Promise<Article> => {
      const response = await apiClient.put<Article>(`/api/articles/${slug}`, data);
      return response.data as Article;
    },

    delete: async (slug: string): Promise<{ success: boolean }> => {
      const response = await apiClient.delete(`/api/articles/${slug}`);
      return response.data as { success: boolean };
    },
  },

  // Médiá
  media: {
    upload: async (file: File): Promise<MediaFile> => {
      const formData = new FormData();
      formData.append('file', file);
      const response = await apiClient.post<MediaFile>(
        '/api/media/upload',
        formData,
        {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        }
      );
      return response.data as MediaFile;
    },

    getAll: async (params?: { type?: string; search?: string }): Promise<MediaFile[]> => {
      const response = await apiClient.get<MediaFile[]>('/api/media', { params });
      return response.data || [];
    },

    delete: async (path: string): Promise<{ success: boolean }> => {
      const response = await apiClient.delete(`/api/media/${encodeURIComponent(path)}`);
      return response.data as { success: boolean };
    },

    update: async (path: string, data: { altText?: string }): Promise<MediaFile> => {
      const response = await apiClient.patch<MediaFile>(`/api/media/${encodeURIComponent(path)}`, data);
      return response.data as MediaFile;
    },
  },
};
