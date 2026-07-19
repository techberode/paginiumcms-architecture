// frontend/src/api/cache.ts
import apiClient, { ApiResponse } from './client';

export type CachePurgeScope = 'content' | 'all';

export interface CacheGenerations {
  pages: number;
  articles: number;
  feeds: number;
}

export interface CacheStats {
  storage_path: string;
  file_entries: number;
  generations: CacheGenerations;
}

export interface CachePurgeResult {
  scope: CachePurgeScope;
  file_entries_before: number;
  file_entries_after: number;
}

export async function getCacheStats(): Promise<CacheStats | null> {
  const res = await apiClient.get<CacheStats>('/api/admin/cache');
  return res.success && res.data ? res.data : null;
}

export async function purgeCache(scope: CachePurgeScope): Promise<ApiResponse<CachePurgeResult>> {
  return apiClient.post<CachePurgeResult>('/api/admin/cache/purge', { scope });
}
