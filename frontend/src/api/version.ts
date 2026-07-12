// frontend/src/api/version.ts
import apiClient from './client';
import { Version } from './types';

export const versionApi = {
  // Získanie histórie verzií
  getHistory: async (contentId: string): Promise<Version[]> => {
    const response = await apiClient.get<Version[]>(`/api/admin/versions/${contentId}`);
    return response.data || [];
  },

  // Získanie konkrétnej verzie
  getVersion: async (contentId: string, version: number): Promise<Version> => {
    const response = await apiClient.get<Version>(`/api/admin/versions/${contentId}/${version}`);
    return response.data as Version;
  },

  // Obnovenie verzie
  restore: async (contentId: string, version: number): Promise<{ success: boolean }> => {
    const response = await apiClient.post('/api/admin/versions/restore', {
      content_id: contentId,
      version,
    });
    return response.data as { success: boolean };
  },

  // Porovnanie verzií
  compare: async (
    contentId: string,
    version1: number,
    version2: number
  ): Promise<{
    version1: { number: number; timestamp: string; author: string };
    version2: { number: number; timestamp: string; author: string };
    diff: any;
    summary: string;
  }> => {
    const response = await apiClient.get('/api/admin/versions/compare', {
      params: {
        content_id: contentId,
        version1,
        version2,
      },
    });
    return response.data as any;
  },

  // Získanie štatistík
  getStats: async (): Promise<{
    total_versions: number;
    total_content_items: number;
    by_type: Record<string, number>;
    recent_versions: Version[];
    largest_files: Array<{ content_id: string; version: number; size: number; type: string }>;
  }> => {
    const response = await apiClient.get('/api/admin/versions/stats');
    return response.data as any;
  },

  // Vyčistenie starých verzií
  cleanup: async (contentId: string, keep?: number): Promise<{ success: boolean; deleted: number }> => {
    const response = await apiClient.delete(`/api/admin/versions/${contentId}`, {
      params: { keep: keep || 10 },
    });
    return response.data as { success: boolean; deleted: number };
  },
};
