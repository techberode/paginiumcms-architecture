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

  save: async (name: string, definitionJson: string) =>
    apiClient.put<ShortcodeListItem>(`/api/admin/shortcodes/${encodeURIComponent(name)}`, definitionJson, {
      headers: { 'Content-Type': 'application/json' },
    }),

  preview: async (definitionJson: string) =>
    apiClient.post<ShortcodePreviewResult>('/api/admin/shortcodes/preview', definitionJson, {
      headers: { 'Content-Type': 'application/json' },
    }),

  delete: async (name: string) =>
    apiClient.delete(`/api/admin/shortcodes/${encodeURIComponent(name)}`),
};
