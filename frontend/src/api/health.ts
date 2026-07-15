// frontend/src/api/health.ts
import apiClient from './client';
import { HealthCheck, HealthReport } from './types';

export const healthApi = {
  runAll: async (group?: string): Promise<HealthReport | null> => {
    const response = await apiClient.get<HealthReport>('/api/admin/health', {
      params: group ? { group } : undefined,
    });
    return response.success && response.data ? response.data : null;
  },

  runCheck: async (name: string): Promise<HealthCheck | null> => {
    const response = await apiClient.get<HealthCheck>(`/api/admin/health/${encodeURIComponent(name)}`);
    return response.success && response.data ? response.data : null;
  },

  getChecks: async (): Promise<{ checks: HealthCheck[]; groups: Record<string, string[]> } | null> => {
    const response = await apiClient.get<{ checks: HealthCheck[]; groups: Record<string, string[]> }>(
      '/api/admin/health/checks'
    );
    return response.success && response.data ? response.data : null;
  },
};

export default healthApi;
