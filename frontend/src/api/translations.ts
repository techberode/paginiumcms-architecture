// frontend/src/api/translations.ts
import apiClient from './client';

export interface TranslationCatalogFile {
  path: string;
  source: 'backend' | 'frontend';
  locale: string;
  module: string;
  name: string;
  size: number;
  modified: number;
  extension: string;
  language: string;
}

export interface TranslationLocaleDef {
  code: string;
  label: string;
  builtin?: boolean;
}

export interface TranslationCatalog {
  sources: Array<{ id: string; label: string; locales: string[] }>;
  locales?: TranslationLocaleDef[];
  files: TranslationCatalogFile[];
}

export interface TranslationFilePayload {
  path: string;
  content: string;
  language: string;
  info?: {
    path: string;
    name: string;
    size: number;
    modified: number;
    language: string;
    backups?: string[];
  };
}

export interface TranslationPolicyError {
  code: string;
  message: string;
  line?: number;
  hint?: string;
}

export const translationsApi = {
  getCatalog: async (): Promise<TranslationCatalog> => {
    const response = await apiClient.get<TranslationCatalog>('/api/admin/translations/catalog');
    return response.data ?? { sources: [], files: [] };
  },

  getFile: async (path: string): Promise<TranslationFilePayload | null> => {
    const response = await apiClient.get<TranslationFilePayload>('/api/admin/translations/file', {
      params: { path },
    });
    if (!response.success || !response.data) {
      return null;
    }
    return {
      ...response.data,
      content: response.data.content ?? '',
      path: response.data.path ?? path,
      language: response.data.language ?? 'plaintext',
    };
  },

  saveFile: async (
    path: string,
    content: string
  ): Promise<{
    success: boolean;
    error?: string;
    errors?: TranslationPolicyError[];
    rejected_path?: string;
  }> => {
    const response = await apiClient.post<{
      errors?: TranslationPolicyError[];
      rejected_path?: string;
    }>('/api/admin/translations/save', { path, content });
    const payload = response.data;
    return {
      success: Boolean(response.success),
      error: response.error,
      errors: payload?.errors,
      rejected_path: payload?.rejected_path,
    };
  },

  getBackups: async (path: string): Promise<string[]> => {
    const response = await apiClient.get<{ backups?: string[] }>('/api/admin/translations/backups', {
      params: { path },
    });
    return response.data?.backups ?? [];
  },

  restoreBackup: async (path: string, backupFile: string): Promise<string | null> => {
    const response = await apiClient.post<{ content?: string }>('/api/admin/translations/restore', {
      path,
      backup_file: backupFile,
    });
    if (!response.success) {
      return null;
    }
    return response.data?.content ?? null;
  },

  createLocale: async (
    code: string,
    label: string,
    copyFrom = 'sk'
  ): Promise<{ success: boolean; error?: string; catalog?: TranslationCatalog }> => {
    const response = await apiClient.post<{ catalog?: TranslationCatalog }>('/api/admin/translations/locales', {
      code,
      label,
      copy_from: copyFrom,
    });
    return {
      success: Boolean(response.success),
      error: response.error,
      catalog: (response.data as { catalog?: TranslationCatalog } | undefined)?.catalog,
    };
  },
};
