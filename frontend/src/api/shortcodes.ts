import { apiClient } from './client';
import type { BulkBatchResult } from '../types/bulk';

export interface ShortcodeListItem {
  name: string;
  enabled: boolean;
  version: number;
  updatedAt: string;
}

export interface ShortcodeDefinition {
  name: string;
  version: number;
  attrs: Record<string, { type: string; options?: string[]; accept?: string; default?: boolean | string }>;
  expand: string;
}

export interface ShortcodePreviewResult {
  valid: boolean;
  definition: ShortcodeDefinition;
}

export const shortcodesApi = {
  list: async (): Promise<ShortcodeListItem[]> => {
    const response = await apiClient.get<{ shortcodes: ShortcodeListItem[] }>('/api/admin/shortcodes');
    if (!response.success) {
      throw new Error(response.error ?? 'Failed to load shortcodes');
    }
    return response.data?.shortcodes ?? [];
  },

  get: async (name: string) =>
    apiClient.get<{ record: ShortcodeListItem; definition: ShortcodeDefinition }>(
      `/api/admin/shortcodes/${encodeURIComponent(name)}`
    ),

  save: async (name: string, definition: unknown) =>
    apiClient.put<ShortcodeListItem>(`/api/admin/shortcodes/${encodeURIComponent(name)}`, definition),

  preview: async (definition: unknown) =>
    apiClient.post<ShortcodePreviewResult>('/api/admin/shortcodes/preview', definition),

  renderMarkup: async (markup: string) =>
    apiClient.post<{ html: string }>('/api/admin/shortcodes/render-markup', { markup }),

  delete: async (name: string) =>
    apiClient.delete(`/api/admin/shortcodes/${encodeURIComponent(name)}`),

  bulkDelete: async (ids: string[]): Promise<BulkBatchResult | null> => {
    const response = await apiClient.post<BulkBatchResult>('/api/admin/shortcodes/bulk-delete', { ids });
    return response.success && response.data ? response.data : null;
  },
};
