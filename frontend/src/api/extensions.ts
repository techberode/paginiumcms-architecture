import { apiClient } from './client';
import type { BulkBatchResult } from '../types/bulk';

export interface ExtensionRecord {
  id: string;
  name: string;
  version: string;
  description: string;
  author?: string;
  enabled: boolean;
  installedAt: string;
  present: boolean;
  hasRoutes?: boolean;
  hasFrontend?: boolean;
  autoDisabled?: boolean;
  disabledReason?: string;
}

export interface ExtensionImportResult {
  id: string;
  name: string;
  version: string;
  enabled: boolean;
  installedAt: string;
}

export type ExtensionImportResponse =
  | { ok: true; data: ExtensionImportResult }
  | { ok: false; data: null; error?: string };

export const extensionsApi = {
  list: async (): Promise<ExtensionRecord[]> => {
    const response = await apiClient.get<{ extensions: ExtensionRecord[] }>('/api/admin/extensions');
    return response.success && response.data ? response.data.extensions : [];
  },

  enable: async (id: string) => apiClient.put(`/api/admin/extensions/${encodeURIComponent(id)}/enable`),

  disable: async (id: string) => apiClient.put(`/api/admin/extensions/${encodeURIComponent(id)}/disable`),

  uninstall: async (id: string) => apiClient.delete(`/api/admin/extensions/${encodeURIComponent(id)}`),

  bulkUninstall: async (ids: string[]): Promise<BulkBatchResult | null> => {
    const response = await apiClient.post<BulkBatchResult>('/api/admin/extensions/bulk-uninstall', { ids });
    return response.success && response.data ? response.data : null;
  },

  importArchive: async (file: File): Promise<ExtensionImportResponse> => {
    const formData = new FormData();
    formData.append('file', file);

    const response = await apiClient.post<ExtensionImportResult>('/api/admin/extensions/import', formData);
    if (response.success && response.data) {
      return { ok: true, data: response.data };
    }

    return {
      ok: false,
      data: null,
      error: typeof response.error === 'string' ? response.error : undefined,
    };
  },
};
