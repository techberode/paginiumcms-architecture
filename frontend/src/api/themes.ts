import { apiClient } from './client';

export interface ThemeRecord {
  id: string;
  name: string;
  version: string;
  enabled: boolean;
  active?: boolean;
  bundled?: boolean;
  installedAt: string;
  present: boolean;
}

export interface ThemeListResponse {
  themes: ThemeRecord[];
  activeThemeId: string;
  previousThemeId: string | null;
  coreThemeId: string;
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

export interface ThemeFileListItem {
  relativePath: string;
  language: string;
  tab: string;
  size: number;
  tooLarge: boolean;
}

export interface ThemeFileListResponse {
  themeId: string;
  files: ThemeFileListItem[];
}

export interface ThemeFileContent {
  relativePath: string;
  content: string;
  language: string;
  tab: string;
  size: number;
}

export interface ThemeValidateMarker {
  line: number;
  message: string;
  endLine?: number;
}

export interface ThemeValidateResult {
  valid: boolean;
  relativePath: string;
  markers: ThemeValidateMarker[];
}

export const themesApi = {
  list: async (): Promise<ThemeListResponse> => {
    const response = await apiClient.get<ThemeListResponse>('/api/admin/themes');
    if (response.success && response.data) {
      return response.data;
    }

    return { themes: [], activeThemeId: 'paginium-core', previousThemeId: null, coreThemeId: 'paginium-core' };
  },

  activate: async (id: string) => apiClient.post<{ activeThemeId: string; previousThemeId: string | null }>(
    `/api/admin/themes/${encodeURIComponent(id)}/activate`,
    {},
  ),

  deactivate: async () => apiClient.post<{ activeThemeId: string; previousThemeId: string | null }>(
    '/api/admin/themes/deactivate',
    {},
  ),

  rollback: async () => apiClient.post<{ activeThemeId: string; previousThemeId: string | null }>(
    '/api/admin/themes/rollback',
    {},
  ),

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

  listFiles: (id: string) =>
    apiClient.get<ThemeFileListResponse>(`/api/admin/themes/${encodeURIComponent(id)}/files`),

  getFile: (id: string, path: string) =>
    apiClient.get<ThemeFileContent>(`/api/admin/themes/${encodeURIComponent(id)}/file`, {
      params: { path },
    }),

  validate: async (input: {
    themeId: string;
    relativePath: string;
    content: string;
  }): Promise<{ ok: boolean; result: ThemeValidateResult | null; error?: string }> => {
    const response = await apiClient.post<ThemeValidateResult>('/api/admin/themes/validate', input);
    if (response.data && Array.isArray(response.data.markers)) {
      return {
        ok: response.success && response.data.valid,
        result: response.data,
        error: response.success ? undefined : (response.error ?? undefined),
      };
    }

    return { ok: false, result: null, error: response.error };
  },
};
