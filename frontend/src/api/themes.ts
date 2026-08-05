import { apiClient } from './client';

export interface ThemeRecord {
  id: string;
  name: string;
  version: string;
  enabled: boolean;
  installedAt: string;
  present: boolean;
}

export interface ThemeImportResult {
  id: string;
  name: string;
  version: string;
  enabled: boolean;
  installedAt: string;
}

export const themesApi = {
  list: async (): Promise<ThemeRecord[]> => {
    const response = await apiClient.get<{ themes: ThemeRecord[] }>('/api/admin/themes');
    return response.success && response.data ? response.data.themes : [];
  },

  uninstall: async (id: string) => apiClient.delete(`/api/admin/themes/${encodeURIComponent(id)}`),

  importArchive: async (file: File): Promise<ThemeImportResult | null> => {
    const formData = new FormData();
    formData.append('file', file);

    const response = await fetch('/api/admin/themes/import', {
      method: 'POST',
      body: formData,
      credentials: 'include',
    });

    const payload = await response.json();
    return payload.success && payload.data ? (payload.data as ThemeImportResult) : null;
  },
};
