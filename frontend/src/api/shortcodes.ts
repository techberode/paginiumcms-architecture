import { apiClient } from './client';

export interface ShortcodeListItem {
  name: string;
  enabled: boolean;
  version: number;
  updatedAt: string;
}

export interface ShortcodeDefinition {
  name: string;
  version: number;
  attrs: Record<string, { type: string; options?: string[] }>;
  expand: string;
}

export interface ShortcodePreviewResult {
  valid: boolean;
  definition: ShortcodeDefinition;
}

export const shortcodesApi = {
  list: async (): Promise<ShortcodeListItem[]> => {
    const response = await apiClient.get<{ shortcodes: ShortcodeListItem[] }>('/api/admin/shortcodes');
    return response.success && response.data ? response.data.shortcodes : [];
  },

  get: async (name: string) =>
    apiClient.get<{ record: ShortcodeListItem; definition: ShortcodeDefinition }>(
      `/api/admin/shortcodes/${encodeURIComponent(name)}`
    ),

  save: async (name: string, definition: unknown) =>
    apiClient.put<ShortcodeListItem>(`/api/admin/shortcodes/${encodeURIComponent(name)}`, definition),

  preview: async (definition: unknown) =>
    apiClient.post<ShortcodePreviewResult>('/api/admin/shortcodes/preview', definition),

  delete: async (name: string) =>
    apiClient.delete(`/api/admin/shortcodes/${encodeURIComponent(name)}`),
};
