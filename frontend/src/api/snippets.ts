import { apiClient } from './client';
import type { BulkBatchResult } from '../types/bulk';

export interface SnippetListItem {
  name: string;
  title: string;
  enabled: boolean;
  version: number;
  updatedAt: string;
}

export interface SnippetDocument {
  name: string;
  title: string;
  body: string;
  format: 'markdown' | 'html';
  version: number;
  enabled: boolean;
  updatedAt?: string;
}

export const snippetsApi = {
  list: async (): Promise<SnippetListItem[]> => {
    const response = await apiClient.get<{ snippets: SnippetListItem[] }>('/api/admin/snippets');
    return response.success && response.data ? response.data.snippets : [];
  },

  get: async (name: string) =>
    apiClient.get<{ record: SnippetListItem; snippet: SnippetDocument }>(
      `/api/admin/snippets/${encodeURIComponent(name)}`
    ),

  save: async (name: string, snippet: SnippetDocument) =>
    apiClient.put<{ snippet: SnippetListItem; invalidatedReferences: number }>(
      `/api/admin/snippets/${encodeURIComponent(name)}`,
      snippet
    ),

  delete: async (name: string) => apiClient.delete(`/api/admin/snippets/${encodeURIComponent(name)}`),

  bulkDelete: async (ids: string[]): Promise<BulkBatchResult | null> => {
    const response = await apiClient.post<BulkBatchResult>('/api/admin/snippets/bulk-delete', { ids });
    return response.success && response.data ? response.data : null;
  },
};

export function buildSnippetInsertTag(name: string): string {
  return `[snippet name="${name}"/]`;
}
