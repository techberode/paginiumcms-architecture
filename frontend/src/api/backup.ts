// frontend/src/api/backup.ts
import apiClient from './client';
import type { Backup, BackupVerifyResult, ScheduleInfo } from './types';
import type { BulkBatchResult } from '../types/bulk';

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

  importArchive: async (file: File, name?: string): Promise<Backup | null> => {
    const formData = new FormData();
    formData.append('file', file);
    if (name?.trim()) {
      formData.append('name', name.trim());
    }

    const response = await fetch('/api/admin/backups/import', {
      method: 'POST',
      body: formData,
      credentials: 'include',
    });
    const payload = await response.json();
    return payload.success && payload.data ? (payload.data as Backup) : null;
  },

  download: async (id: string, filename: string): Promise<{ ok: boolean; sha256?: string }> => {
    try {
      const response = await fetch(`/api/admin/backups/${encodeURIComponent(id)}/download`, {
        credentials: 'include',
      });
      if (!response.ok) {
        return { ok: false };
      }
      const blob = await response.blob();
      const sha256 = response.headers.get('X-Backup-SHA256') ?? undefined;
      const url = window.URL.createObjectURL(blob);
      const anchor = document.createElement('a');
      anchor.href = url;
      anchor.download = `${filename}.zip`;
      document.body.appendChild(anchor);
      anchor.click();
      window.URL.revokeObjectURL(url);
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

  restore: async (id: string): Promise<boolean> => {
    const response = await apiClient.post(`/api/admin/backups/${encodeURIComponent(id)}/restore`);
    return Boolean(response.success);
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

  schedule: async (interval: 'daily' | 'weekly' | 'monthly', keep: number): Promise<boolean> => {
    const response = await apiClient.post('/api/admin/backups/schedule', {
      interval,
      keep,
    });
    return Boolean(response.success);
  },

  getSchedule: async (): Promise<ScheduleInfo | null> => {
    const response = await apiClient.get<ScheduleInfo>('/api/admin/backups/schedule');
    return response.success && response.data ? response.data : null;
  },
};
