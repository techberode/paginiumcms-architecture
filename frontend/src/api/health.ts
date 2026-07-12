// frontend/src/api/health.ts
import apiClient from './client';
import { HealthCheck, HealthReport } from './types';

export const healthApi = {
  // Spustenie všetkých kontrol
  runAll: async (group?: string): Promise<HealthReport> => {
    const response = await apiClient.get<HealthReport>('/api/admin/health', {
      params: { group },
    });
    return response.data as HealthReport;
  },

  // Spustenie konkrétnej kontroly
  runCheck: async (name: string): Promise<HealthCheck> => {
    const response = await apiClient.get<HealthCheck>(`/api/admin/health/${name}`);
    return response.data as HealthCheck;
  },

  // Získanie zoznamu kontrol
  getChecks: async (): Promise<{ checks: HealthCheck[]; groups: Record<string, string[]> }> => {
    const response = await apiClient.get('/api/admin/health/checks');
    return response.data as { checks: HealthCheck[]; groups: Record<string, string[]> };
  },
};
