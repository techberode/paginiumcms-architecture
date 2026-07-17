// frontend/src/api/audit.ts
import apiClient from './client';
import { AuditEvent, AuditStats } from './types';

interface AuditTrailPayload {
  events: AuditEvent[];
  total: number;
}

export const auditApi = {
  getContentAudit: async (contentId: string, limit?: number): Promise<AuditEvent[]> => {
    const response = await apiClient.get<AuditTrailPayload>(
      `/api/admin/audit/content/${encodeURIComponent(contentId)}`,
      { params: { limit: limit || 100 } }
    );
    return response.success && response.data?.events ? response.data.events : [];
  },

  getUserAudit: async (userId: string, limit?: number): Promise<AuditEvent[]> => {
    const response = await apiClient.get<AuditTrailPayload>(
      `/api/admin/audit/user/${encodeURIComponent(userId)}`,
      { params: { limit: limit || 100 } }
    );
    return response.success && response.data?.events ? response.data.events : [];
  },

  getStats: async (filters?: {
    category?: string;
    action?: string;
    user_id?: string;
    severity?: string;
  }): Promise<AuditStats | null> => {
    const response = await apiClient.get<AuditStats>('/api/admin/audit/stats', {
      params: filters,
    });
    return response.success && response.data ? response.data : null;
  },

  exportCsv: async (filters?: {
    category?: string;
    action?: string;
    user_id?: string;
    severity?: string;
  }): Promise<Blob> => {
    const response = await apiClient.get('/api/admin/audit/export', {
      params: filters,
      responseType: 'blob',
    });
    return response.data as Blob;
  },

  logEvent: async (data: {
    category: string;
    target: string;
    action: string;
    metadata?: Record<string, unknown>;
    severity?: string;
  }): Promise<boolean> => {
    const response = await apiClient.post('/api/admin/audit/log', data);
    return Boolean(response.success);
  },
};
