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
};
