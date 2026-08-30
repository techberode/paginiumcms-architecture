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

export interface ThemeImportResponse {
  ok: boolean;
  data: ThemeImportResult | null;
  error?: string;
}

export const themesApi = {
  list: async (): Promise<ThemeRecord[]> => {
    const response = await apiClient.get<{ themes: ThemeRecord[] }>('/api/admin/themes');
    return response.success && response.data ? response.data.themes : [];
  },

  uninstall: async (id: string) => apiClient.delete(`/api/admin/themes/${encodeURIComponent(id)}`),

  importArchive: async (file: File): Promise<ThemeImportResponse> => {
    const formData = new FormData();
    formData.append('file', file);

    const response = await fetch('/api/admin/themes/import', {
      method: 'POST',
      body: formData,
      credentials: 'include',
    });

    const payload = await response.json();
    if (payload.success && payload.data) {
      return { ok: true, data: payload.data as ThemeImportResult };
    }

    const error =
      typeof payload.error === 'string'
        ? payload.error
        : typeof payload.message === 'string'
          ? payload.message
          : undefined;

    return { ok: false, data: null, error };
  },

  downloadStarterPackage: (id: string): void => {
    window.location.assign(`/api/admin/themes/starter-package/${encodeURIComponent(id)}`);
  },
};
