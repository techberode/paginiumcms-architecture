// frontend/src/api/audit.ts
import apiClient from './client';
import { AuditEvent, AuditStats } from './types';

export const auditApi = {
  // Získanie auditu pre obsah
  getContentAudit: async (contentId: string, limit?: number): Promise<AuditEvent[]> => {
    const response = await apiClient.get<AuditEvent[]>(`/api/admin/audit/content/${contentId}`, {
      params: { limit: limit || 100 },
    });
    return response.data || [];
  },

  // Získanie auditu pre používateľa
  getUserAudit: async (userId: string, limit?: number): Promise<AuditEvent[]> => {
    const response = await apiClient.get<AuditEvent[]>(`/api/admin/audit/user/${userId}`, {
      params: { limit: limit || 100 },
    });
    return response.data || [];
  },

  // Získanie štatistík
  getStats: async (filters?: {
    category?: string;
    action?: string;
    user_id?: string;
    severity?: string;
  }): Promise<AuditStats> => {
    const response = await apiClient.get<AuditStats>('/api/admin/audit/stats', {
      params: filters,
    });
    return response.data as AuditStats;
  },

  // Export auditu do CSV
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

  // Manuálne logovanie udalosti
  logEvent: async (data: {
    category: string;
    target: string;
    action: string;
    metadata?: Record<string, any>;
    severity?: string;
  }): Promise<{ success: boolean }> => {
    const response = await apiClient.post('/api/admin/audit/log', data);
    return response.data as { success: boolean };
  },
};
