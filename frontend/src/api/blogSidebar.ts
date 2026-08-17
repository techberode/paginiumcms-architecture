import apiClient from './client';

export interface BlogSidebarCategory {
  slug: string;
  label: string;
}

export interface BlogSidebarArticle {
  slug: string;
  title: string;
  excerpt: string;
  createdAt: string;
  tags: string[];
  views?: number;
}

export interface BlogSidebarPayload {
  enabled: boolean;
  placement: 'left' | 'right';
  tags: string[];
  categories: BlogSidebarCategory[];
  latest: BlogSidebarArticle[];
  popular: BlogSidebarArticle[];
}

export const blogSidebarApi = {
  fetch: async (): Promise<BlogSidebarPayload | null> => {
    const response = await apiClient.get<BlogSidebarPayload>('/api/blog/sidebar');
    return response.success && response.data ? response.data : null;
  },
};
