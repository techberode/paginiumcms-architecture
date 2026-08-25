import apiClient from './client';
import type { BulkBatchResult } from '../types/bulk';

export interface CustomRole {
  id: string;
  name: string;
  permissions: string[];
  system: boolean;
}

export const rolesApi = {
  list: async (): Promise<{ roles: CustomRole[]; permissions: string[] }> => {
    const response = await apiClient.get<{ roles: CustomRole[]; permissions: string[] }>('/api/admin/roles');
    if (response.success && response.data) {
      return {
        roles: response.data.roles ?? [],
        permissions: response.data.permissions ?? [],
      };
    }

    return { roles: [], permissions: [] };
  },

  create: async (payload: { id: string; name: string; permissions: string[] }): Promise<CustomRole | null> => {
    const response = await apiClient.post<CustomRole>('/api/admin/roles', payload);
    return response.success && response.data ? response.data : null;
  },

  update: async (
    id: string,
    payload: { name?: string; permissions?: string[] }
  ): Promise<CustomRole | null> => {
    const response = await apiClient.put<CustomRole>(`/api/admin/roles/${encodeURIComponent(id)}`, payload);
    return response.success && response.data ? response.data : null;
  },

  remove: async (id: string): Promise<boolean> => {
    const response = await apiClient.delete<{ id: string; removed: boolean }>(
      `/api/admin/roles/${encodeURIComponent(id)}`
    );
    return response.success;
  },

  bulkDelete: async (ids: string[]): Promise<BulkBatchResult | null> => {
    const response = await apiClient.post<BulkBatchResult>('/api/admin/roles/bulk-delete', { ids });
    return response.success && response.data ? response.data : null;
  },
};

export function isValidRoleId(id: string): boolean {
  return /^(SUPER_ADMIN|[A-Z][A-Z0-9_]{1,31})$/.test(id.trim());
}

export function normalizeRoleId(id: string): string {
  return id.trim().toUpperCase().replace(/[^A-Z0-9_]/g, '_').replace(/^_+/, '').slice(0, 32);
}
