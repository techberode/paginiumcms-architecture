// frontend/src/api/backup.ts
import apiClient from './client';
import { Backup, ScheduleInfo } from './types';

export const backupApi = {
  // Získanie zoznamu záloh
  getAll: async (): Promise<Backup[]> => {
    const response = await apiClient.get<Backup[]>('/api/admin/backups');
    return response.data || [];
  },

  // Vytvorenie zálohy
  create: async (name: string, includes?: string[]): Promise<Backup> => {
    const response = await apiClient.post<Backup>('/api/admin/backups', {
      name,
      includes: includes || ['content', 'config', 'data'],
    });
    return response.data as Backup;
  },

  // Stiahnutie zálohy
  download: async (id: string): Promise<Blob> => {
    const response = await apiClient.get(`/api/admin/backups/${id}/download`, {
      responseType: 'blob',
    });
    return response.data as Blob;
  },

  // Obnovenie zálohy
  restore: async (id: string): Promise<{ success: boolean }> => {
    const response = await apiClient.post(`/api/admin/backups/${id}/restore`);
    return response.data as { success: boolean };
  },

  // Vymazanie zálohy
  delete: async (id: string): Promise<{ success: boolean }> => {
    const response = await apiClient.delete(`/api/admin/backups/${id}`);
    return response.data as { success: boolean };
  },

  // Naplánovanie zálohovania
  schedule: async (interval: 'daily' | 'weekly' | 'monthly', keep: number): Promise<{ success: boolean }> => {
    const response = await apiClient.post('/api/admin/backups/schedule', {
      interval,
      keep,
    });
    return response.data as { success: boolean };
  },

  // Získanie informácií o pláne
  getSchedule: async (): Promise<ScheduleInfo> => {
    const response = await apiClient.get<ScheduleInfo>('/api/admin/backups/schedule');
    return response.data as ScheduleInfo;
  },
};
