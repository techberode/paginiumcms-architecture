import apiClient from './client';
import type { BulkBatchResult } from '../types/bulk';

export interface ContentCategory {
  slug: string;
  label: string;
}

export const categoriesApi = {
  listPublic: async (): Promise<ContentCategory[]> => {
    const response = await apiClient.get<{ categories: ContentCategory[] }>('/api/categories');
    return response.success && response.data?.categories ? response.data.categories : [];
  },

  listAdmin: async (): Promise<ContentCategory[]> => {
    const response = await apiClient.get<{ categories: ContentCategory[] }>('/api/admin/categories');
    return response.success && response.data?.categories ? response.data.categories : [];
  },

  save: async (slug: string, label: string): Promise<ContentCategory | null> => {
    const response = await apiClient.post<ContentCategory>('/api/admin/categories', { slug, label });
    return response.success && response.data ? response.data : null;
  },

  update: async (slug: string, label: string): Promise<ContentCategory | null> => {
    const response = await apiClient.put<ContentCategory>(`/api/admin/categories/${encodeURIComponent(slug)}`, {
      label,
    });
    return response.success && response.data ? response.data : null;
  },

  remove: async (slug: string): Promise<boolean> => {
    const response = await apiClient.delete<{ slug: string; removed: boolean }>(
      `/api/admin/categories/${encodeURIComponent(slug)}`
    );
    return response.success;
  },

  bulkDelete: async (ids: string[]): Promise<BulkBatchResult | null> => {
    const response = await apiClient.post<BulkBatchResult>('/api/admin/categories/bulk-delete', { ids });
    return response.success && response.data ? response.data : null;
  },
};
