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
  hasThumbnail?: boolean;
}

export interface ThemeSaveResult {
  blocked: boolean;
  themeId: string;
  written: string[];
  frontendCssCopied: boolean;
  issues: ThemePreviewIssue[];
}

export function themeThumbnailUrl(id: string, bust = 0): string {
  const query = bust > 0 ? `?t=${bust}` : '';
  return `/api/admin/themes/${encodeURIComponent(id)}/thumbnail${query}`;
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

export interface ThemePreviewIssue {
  relativePath: string;
  valid: boolean;
  markers: ThemeValidateMarker[];
}

export interface ThemePreviewResult {
  blocked: boolean;
  document: string;
  template: string;
  issues: ThemePreviewIssue[];
}

export interface ThemeNormalizeMarker {
  line: number;
  message: string;
  relativePath?: string;
}

export interface ThemeNormalizeResult {
  rejected: boolean;
  files: Record<string, string>;
  dropped: string[];
  markers: ThemeNormalizeMarker[];
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

  preview: async (input: {
    themeId: string;
    files: Record<string, string>;
    template?: string;
  }): Promise<{ ok: boolean; result: ThemePreviewResult | null; error?: string }> => {
    const response = await apiClient.post<ThemePreviewResult>('/api/admin/themes/preview', input);
    if (response.data && typeof response.data.document === 'string') {
      return {
        ok: response.success && !response.data.blocked,
        result: response.data,
        error: response.success ? undefined : (response.error ?? undefined),
      };
    }

    return { ok: false, result: null, error: response.error };
  },

  normalize: async (input: {
    themeId: string;
    files?: Record<string, string>;
    html?: string;
    css?: string;
    js?: string;
  }): Promise<{ ok: boolean; result: ThemeNormalizeResult | null; error?: string }> => {
    const response = await apiClient.post<ThemeNormalizeResult>('/api/admin/themes/normalize', input);
    if (response.data && Array.isArray(response.data.dropped) && response.data.files) {
      return {
        ok: response.success && !response.data.rejected,
        result: response.data,
        error: response.success ? undefined : (response.error ?? undefined),
      };
    }

    return { ok: false, result: null, error: response.error };
  },

  save: async (input: {
    themeId: string;
    files: Record<string, string>;
  }): Promise<{ ok: boolean; result: ThemeSaveResult | null; error?: string }> => {
    const response = await apiClient.post<ThemeSaveResult>('/api/admin/themes/save', input);
    if (response.data && Array.isArray(response.data.written) && Array.isArray(response.data.issues)) {
      return {
        ok: response.success && !response.data.blocked,
        result: response.data,
        error: response.success ? undefined : (response.error ?? undefined),
      };
    }

    return { ok: false, result: null, error: response.error };
  },

  uploadThumbnail: async (
    id: string,
    file: File,
  ): Promise<{ ok: boolean; hasThumbnail: boolean; error?: string }> => {
    const form = new FormData();
    form.append('file', file);
    const response = await apiClient.post<{ themeId: string; hasThumbnail: boolean }>(
      `/api/admin/themes/${encodeURIComponent(id)}/thumbnail`,
      form,
    );
    if (response.success && response.data?.hasThumbnail) {
      return { ok: true, hasThumbnail: true };
    }

    return { ok: false, hasThumbnail: false, error: response.error };
  },
};
