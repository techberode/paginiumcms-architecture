// frontend/src/api/trash.ts
import apiClient from './client';
import type { BulkBatchResult } from '../types/bulk';

export interface TrashItem {
  id: string;
  originalPath: string;
  deletedAt: string;
  filename: string;
  size: number;
}

export interface TrashRestoreResult {
  originalPath: string;
}

export interface TrashBackupResult {
  filename: string;
  size: number;
  count: number;
  downloadUrl: string;
}

export const trashApi = {
  list: async (): Promise<TrashItem[]> => {
    const response = await apiClient.get<TrashItem[]>('/api/admin/trash');
    return response.success && Array.isArray(response.data) ? response.data : [];
  },

  restore: async (id: string): Promise<TrashRestoreResult | null> => {
    const response = await apiClient.post<TrashRestoreResult>(`/api/admin/trash/${id}/restore`);
    return response.success && response.data ? response.data : null;
  },

  bulkRestore: async (ids: string[]): Promise<BulkBatchResult | null> => {
    const response = await apiClient.post<BulkBatchResult>('/api/admin/trash/bulk-restore', { ids });
    return response.success && response.data ? response.data : null;
  },

  bulkPurge: async (ids: string[]): Promise<BulkBatchResult | null> => {
    const response = await apiClient.post<BulkBatchResult>('/api/admin/trash/bulk-purge', { ids });
    return response.success && response.data ? response.data : null;
  },

  bulkBackup: async (ids: string[]): Promise<TrashBackupResult | null> => {
    const response = await apiClient.post<TrashBackupResult>('/api/admin/trash/bulk-backup', { ids });
    return response.success && response.data ? response.data : null;
  },

  emptyTrash: async (): Promise<number | null> => {
    const response = await apiClient.post<{ removed: number }>('/api/admin/trash/empty');
    return response.success && response.data ? response.data.removed : null;
  },

  downloadBackup: async (downloadUrl: string, filename: string): Promise<boolean> => {
    try {
      const response = await fetch(downloadUrl, { credentials: 'include' });
      if (!response.ok) {
        return false;
      }

      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const anchor = document.createElement('a');
      anchor.href = url;
      anchor.download = filename;
      anchor.click();
      window.URL.revokeObjectURL(url);
      return true;
    } catch {
      return false;
    }
  },
};
