// frontend/src/api/backup.ts
import apiClient from './client';
import type { Backup, BackupVerifyResult, ScheduleInfo } from './types';
import type { BulkBatchResult } from '../types/bulk';
import { resolveApiBaseUrl } from '../utils/apiBaseUrl';

export type BackupImportResult = {
  ok: boolean;
  backup?: Backup;
  error?: string;
  message?: string;
};

export type BackupRestoreResult = {
  ok: boolean;
  error?: string;
  message?: string;
};

export const backupApi = {
  getAll: async (): Promise<Backup[]> => {
    const response = await apiClient.get<Backup[]>('/api/admin/backups');
    return response.success && Array.isArray(response.data) ? response.data : [];
  },

  create: async (name: string, includes?: string[]): Promise<Backup | null> => {
    const response = await apiClient.post<Backup>('/api/admin/backups', {
      name,
      includes: includes || ['content', 'config', 'data'],
    });
    return response.success && response.data ? response.data : null;
  },

  importArchive: async (file: File, name?: string): Promise<BackupImportResult> => {
    const formData = new FormData();
    formData.append('file', file);
    if (name?.trim()) {
      formData.append('name', name.trim());
    }

    const response = await apiClient.post<Backup>('/api/admin/backups/import', formData, {
      timeout: 120000,
    });

    if (response.success && response.data) {
      return {
        ok: true,
        backup: response.data,
        message: response.message,
      };
    }

    return {
      ok: false,
      error: response.error || response.message || 'Import failed',
    };
  },

  download: async (id: string, filename: string): Promise<{ ok: boolean; sha256?: string }> => {
    try {
      const verify = await apiClient.get<BackupVerifyResult>(
        `/api/admin/backups/${encodeURIComponent(id)}/verify`
      );
      const sha256 =
        verify.success && verify.data?.actual
          ? verify.data.actual
          : verify.success && verify.data?.expected
            ? verify.data.expected
            : undefined;

      const url = `${resolveApiBaseUrl()}/api/admin/backups/${encodeURIComponent(id)}/download`;
      const anchor = document.createElement('a');
      anchor.href = url;
      anchor.download = `${filename}.zip`;
      anchor.rel = 'noopener';
      document.body.appendChild(anchor);
      anchor.click();
      document.body.removeChild(anchor);

      return { ok: true, sha256 };
    } catch {
      return { ok: false };
    }
  },

  verify: async (id: string): Promise<BackupVerifyResult | null> => {
    const response = await apiClient.get<BackupVerifyResult>(`/api/admin/backups/${encodeURIComponent(id)}/verify`);
    return response.success && response.data ? response.data : null;
  },

  restore: async (id: string): Promise<BackupRestoreResult> => {
    const response = await apiClient.post<null>(`/api/admin/backups/${encodeURIComponent(id)}/restore`, {}, {
      timeout: 120000,
    });

    return {
      ok: Boolean(response.success),
      message: response.message,
      error: response.error || response.message,
    };
  },

  delete: async (id: string): Promise<boolean> => {
    const response = await apiClient.delete(`/api/admin/backups/${encodeURIComponent(id)}`);
    return Boolean(response.success);
  },

  bulkDelete: async (ids: string[]): Promise<BulkBatchResult | null> => {
    const response = await apiClient.post<BulkBatchResult>('/api/admin/backups/bulk-delete', { ids });
    return response.success && response.data ? response.data : null;
  },

  bulkRestore: async (ids: string[]): Promise<BulkBatchResult | null> => {
    const response = await apiClient.post<BulkBatchResult>('/api/admin/backups/bulk-restore', { ids });
    return response.success && response.data ? response.data : null;
  },

  schedule: async (payload: {
    enabled?: boolean;
    interval?: 'daily' | 'weekly' | 'monthly';
    keep?: number;
  }): Promise<ScheduleInfo | null> => {
    const response = await apiClient.post<ScheduleInfo>('/api/admin/backups/schedule', payload);
    return response.success && response.data ? response.data : null;
  },

  getSchedule: async (): Promise<ScheduleInfo | null> => {
    const response = await apiClient.get<ScheduleInfo>('/api/admin/backups/schedule');
    return response.success && response.data ? response.data : null;
  },
};
