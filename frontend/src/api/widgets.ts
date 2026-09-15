import { apiClient } from './client';

export type WidgetFieldKind = 'string' | 'tone' | 'percent' | 'href';

export interface WidgetFieldSchema {
  key: string;
  kind: WidgetFieldKind;
  options?: string[];
}

export interface WidgetTypeDefinition {
  id: string;
  selfClosing: boolean;
  fields: WidgetFieldSchema[];
  defaults: Record<string, string>;
  source?: 'builtin' | 'custom';
  label?: string;
}

export interface WidgetCustomDefinition {
  id: string;
  label: string;
  version: number;
  selfClosing: boolean;
  fields: WidgetFieldSchema[];
  defaults: Record<string, string>;
  expand: string;
  source: 'custom';
}

export interface WidgetPreviewResult {
  html: string;
  markup: string;
}

export const widgetsApi = {
  list: async (): Promise<WidgetTypeDefinition[]> => {
    const response = await apiClient.get<{ widgets: WidgetTypeDefinition[] }>('/api/admin/widgets');
    return response.success && response.data ? response.data.widgets : [];
  },

  preview: async (payload: { type: string; attrs: Record<string, string>; content?: string }) =>
    apiClient.post<WidgetPreviewResult>('/api/admin/widgets/preview', payload),

  get: async (id: string) =>
    apiClient.get<{ widget: WidgetCustomDefinition }>(`/api/admin/widgets/${encodeURIComponent(id)}`),

  save: async (id: string, definition: unknown) =>
    apiClient.put<{ widget: WidgetCustomDefinition }>(`/api/admin/widgets/${encodeURIComponent(id)}`, definition),

  delete: async (id: string) => apiClient.delete(`/api/admin/widgets/${encodeURIComponent(id)}`),
};
