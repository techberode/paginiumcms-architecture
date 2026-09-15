import apiClient, { type ApiResponse } from './client';

export type ComingSoonKind = 'page' | 'article';

export interface ComingSoonItem {
  id: string;
  contentKind: ComingSoonKind;
  slug: string;
  title: string;
  subtitle: string;
  publishAt: number;
  enabled: boolean;
  embedOnPage: boolean;
  createdAt: number;
  updatedAt: number;
}

export interface ComingSoonPublic {
  id: string;
  contentKind: ComingSoonKind;
  slug: string;
  title: string;
  subtitle: string;
  publishAt: number;
  embedOnPage: boolean;
  remainingSeconds: number;
  isLive: boolean;
}

export interface ComingSoonContentTarget {
  slug: string;
  title: string;
}

export const comingSoonApi = {
  publicBySlug: async (kind: ComingSoonKind, slug: string): Promise<ComingSoonPublic | null> => {
    const response = await apiClient.get<ComingSoonPublic>(
      `/api/coming-soon/${encodeURIComponent(kind)}/${encodeURIComponent(slug)}`
    );
    if (response.success && response.data) {
      return response.data;
    }
    return null;
  },

  list: async (): Promise<ComingSoonItem[]> => {
    const response = await apiClient.get<{ items: ComingSoonItem[] }>('/api/admin/coming-soon');
    return response.success && response.data ? response.data.items ?? [] : [];
  },

  targets: async (): Promise<{ pages: ComingSoonContentTarget[]; articles: ComingSoonContentTarget[] }> => {
    const response = await apiClient.get<{
      pages: ComingSoonContentTarget[];
      articles: ComingSoonContentTarget[];
    }>('/api/admin/coming-soon/targets');
    if (response.success && response.data) {
      return {
        pages: response.data.pages ?? [],
        articles: response.data.articles ?? [],
      };
    }
    return { pages: [], articles: [] };
  },

  create: async (payload: Partial<ComingSoonItem>): Promise<ApiResponse<{ item: ComingSoonItem }>> => {
    return apiClient.post<{ item: ComingSoonItem }>('/api/admin/coming-soon', payload);
  },

  update: async (
    id: string,
    payload: Partial<ComingSoonItem>
  ): Promise<ApiResponse<{ item: ComingSoonItem }>> => {
    return apiClient.put<{ item: ComingSoonItem }>(`/api/admin/coming-soon/${encodeURIComponent(id)}`, payload);
  },

  remove: async (id: string): Promise<boolean> => {
    const response = await apiClient.delete<{ removed: boolean }>(
      `/api/admin/coming-soon/${encodeURIComponent(id)}`
    );
    return response.success;
  },
};
