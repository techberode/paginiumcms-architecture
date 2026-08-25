// frontend/src/api/redirects.ts
import apiClient, { type ApiResponse } from './client';
import type { BulkBatchResult } from '../types/bulk';

export interface RedirectRule {
  id: string;
  from: string;
  to: string;
  status: 301 | 302;
  enabled: boolean;
  createdAt: string;
  updatedAt: string | null;
  note: string;
}

export interface RedirectsIndexResponse {
  rules: RedirectRule[];
  statusOptions: number[];
}

export const redirectsApi = {
  async list(): Promise<RedirectsIndexResponse | null> {
    const response = await apiClient.get<RedirectsIndexResponse>('/api/admin/platform/redirects');
    return response.success && response.data ? response.data : null;
  },

  async create(payload: {
    from: string;
    to: string;
    status?: 301 | 302;
    note?: string;
  }): Promise<ApiResponse<{ rule: RedirectRule }>> {
    return apiClient.post<{ rule: RedirectRule }>('/api/admin/platform/redirects', payload);
  },

  async update(
    id: string,
    payload: Partial<Pick<RedirectRule, 'from' | 'to' | 'status' | 'enabled' | 'note'>>
  ): Promise<ApiResponse<{ rule: RedirectRule }>> {
    return apiClient.put<{ rule: RedirectRule }>(`/api/admin/platform/redirects/${encodeURIComponent(id)}`, payload);
  },

  async remove(id: string): Promise<boolean> {
    const response = await apiClient.delete<{ deleted: boolean }>(
      `/api/admin/platform/redirects/${encodeURIComponent(id)}`
    );
    return response.success;
  },

  async bulkDelete(ids: string[]): Promise<BulkBatchResult | null> {
    const response = await apiClient.post<BulkBatchResult>('/api/admin/platform/redirects/bulk-delete', { ids });
    return response.success && response.data ? response.data : null;
  },
};
