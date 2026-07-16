// frontend/src/api/search.ts
import apiClient from './client';

export interface SearchResultItem {
  slug: string;
  type: 'page' | 'article';
  title: string;
  status: string;
  excerpt: string;
  tags: string[];
  updatedAt: string;
  path: string;
}

export async function searchContent(
  q: string,
  options?: { type?: 'page' | 'article'; limit?: number }
): Promise<SearchResultItem[]> {
  const params: Record<string, string | number> = { q };
  if (options?.type) {
    params.type = options.type;
  }
  if (options?.limit) {
    params.limit = options.limit;
  }

  const res = await apiClient.get<SearchResultItem[]>('/api/search', { params });
  return res.success ? (res.data ?? []) : [];
}
