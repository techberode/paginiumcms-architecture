// frontend/src/api/backup.ts
import apiClient from './client';
import { Backup, ScheduleInfo } from './types';

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

  download: async (id: string): Promise<Blob> => {
    const response = await apiClient.get(`/api/admin/backups/${id}/download`, {
      responseType: 'blob',
    });
    return response.data as Blob;
  },

  restore: async (id: string): Promise<boolean> => {
    const response = await apiClient.post(`/api/admin/backups/${id}/restore`);
    return Boolean(response.success);
  },

  delete: async (id: string): Promise<boolean> => {
    const response = await apiClient.delete(`/api/admin/backups/${id}`);
    return Boolean(response.success);
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
