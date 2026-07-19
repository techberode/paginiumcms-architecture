// frontend/src/api/demo.ts
import apiClient from './client';

export interface DemoStatus {
  enabled: boolean;
  storage_path: string;
  content_path: string;
  file_count: number;
  seeded: boolean;
}

export const demoApi = {
  async status() {
    const response = await apiClient.get<DemoStatus>('/api/admin/demo/status');
    return response.success && response.data ? response.data : null;
  },

  async reset() {
    const response = await apiClient.post<{ written: number; storage_path: string }>(
      '/api/admin/demo/reset'
    );
    return response.success ? response.data : null;
  },
};
