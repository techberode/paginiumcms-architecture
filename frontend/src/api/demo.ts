// frontend/src/api/demo.ts
import apiClient from './client';

export interface DemoStatus {
  enabled: boolean;
  storage_path: string;
  content_path: string;
  file_count: number;
  seeded: boolean;
  auto_reset_minutes?: number;
  last_reset_at?: string | null;
  next_reset_at?: string | null;
  seconds_until_reset?: number | null;
  isolated?: boolean;
}

export interface DemoPublicInfo {
  enabled: boolean;
  loginEmail?: string;
  auto_reset_minutes?: number;
  last_reset_at?: string | null;
  next_reset_at?: string | null;
  seconds_until_reset?: number | null;
  isolated?: boolean;
}

export const demoApi = {
  async status() {
    const response = await apiClient.get<DemoStatus>('/api/admin/demo/status');
    return response.success && response.data ? response.data : null;
  },

  async publicInfo() {
    const response = await apiClient.get<DemoPublicInfo>('/api/demo/public-info');
    return response.success && response.data ? response.data : null;
  },

  async quickLogin() {
    const response = await apiClient.post<{ user: import('./types').User }>('/api/demo/quick-login');
    return response.success && response.data ? response.data : null;
  },

  async reset() {
    const response = await apiClient.post<{ written: number; storage_path: string }>(
      '/api/admin/demo/reset'
    );
    return response.success ? response.data : null;
  },
};
