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
  adminPath?: string;
  subtitle?: string;
}

export type AdminSearchResultType = 'page' | 'article' | 'media' | 'route';

export interface AdminSearchResultItem {
  type: AdminSearchResultType;
  title: string;
  subtitle?: string;
  path: string;
  adminPath: string;
  slug?: string;
  status?: string;
  mimeType?: string;
  routeId?: string;
  updatedAt?: string;
}

export interface AdminSearchResponse {
  query: string;
  scope: 'admin';
  results: AdminSearchResultItem[];
  counts: Record<string, number>;
}

export async function searchContent(
  q: string,
  options?: { type?: 'page' | 'article'; limit?: number; scope?: 'public' }
): Promise<SearchResultItem[]> {
  const params: Record<string, string | number> = { q, scope: options?.scope ?? 'public' };
  if (options?.type) {
    params.type = options.type;
  }
  if (options?.limit) {
    params.limit = options.limit;
  }

  const res = await apiClient.get<SearchResultItem[]>('/api/search', { params });
  return res.success ? (res.data ?? []) : [];
}

export async function searchAdmin(
  q: string,
  options?: { types?: AdminSearchResultType[]; limit?: number }
): Promise<AdminSearchResponse | null> {
  const params: Record<string, string | number> = {
    q,
    scope: 'admin',
  };
  if (options?.types?.length) {
    params.types = options.types.join(',');
  }
  if (options?.limit) {
    params.limit = options.limit;
  }

  const res = await apiClient.get<AdminSearchResponse>('/api/search', { params });
  return res.success ? (res.data ?? null) : null;
}
