// frontend/src/api/security.ts
import apiClient from './client';

export interface SecurityAuditEvent {
  id: string;
  type: string;
  severity: string;
  message: string;
  user_id?: string | null;
  email?: string | null;
  ip?: string | null;
  metadata?: Record<string, unknown>;
  created_at: string;
}

export interface AclRule {
  id: string;
  path: string;
  roles: string[];
  permissions: string[];
  enabled: boolean;
}

export interface SsoProvider {
  id: string;
  name: string;
  type: string;
}

export const securityApi = {
  async listAudit(params?: { type?: string; severity?: string; limit?: number }) {
    const response = await apiClient.get<{ total: number; events: SecurityAuditEvent[] }>(
      '/api/admin/security/audit',
      { params }
    );
    return response.success && response.data ? response.data : { total: 0, events: [] };
  },

  async exportAuditCsv(params?: { type?: string; severity?: string }) {
    const response = await apiClient.get('/api/admin/security/audit/export', {
      params,
      responseType: 'blob',
    });
    return response.data as Blob;
  },

  async getAcl() {
    const response = await apiClient.get<{ enabled: boolean; rules: AclRule[] }>('/api/admin/security/acl');
    return response.success && response.data ? response.data : { enabled: false, rules: [] };
  },

  async saveAcl(payload: { enabled: boolean; rules: AclRule[] }) {
    const response = await apiClient.put<{ enabled: boolean; rules: AclRule[] }>(
      '/api/admin/security/acl',
      payload
    );
    return response.success ? response.data : null;
  },

  async listSsoProviders() {
    const response = await apiClient.get<{ enabled: boolean; providers: SsoProvider[] }>(
      '/api/auth/sso/providers'
    );
    return response.success && response.data ? response.data : { enabled: false, providers: [] };
  },

  async startSso(providerId: string, redirectUri?: string) {
    const params = redirectUri ? { redirect_uri: redirectUri } : undefined;
    const response = await apiClient.get<{ authorizationUrl: string; state: string }>(
      `/api/auth/sso/${encodeURIComponent(providerId)}/start`,
      { params }
    );
    return response.success && response.data ? response.data : null;
  },
};
