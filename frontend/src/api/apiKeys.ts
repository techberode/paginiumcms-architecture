// frontend/src/api/apiKeys.ts
import apiClient, { type ApiResponse } from './client';

export interface ApiKeyMetadata {
  id: string;
  idPrefix: string;
  label: string;
  scopes: string[];
  status: 'active' | 'revoked' | 'expired';
  createdAt: string;
  expiresAt: string | null;
  lastUsedAt: string | null;
  revokedAt: string | null;
  createdBy: string;
}

export interface ApiKeyAuditEvent {
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

export interface ApiKeysIndexResponse {
  keys: ApiKeyMetadata[];
  availableScopes: string[];
  scopeGroups: {
    read: string[];
    write: string[];
    token: string[];
  };
  config?: {
    pepperConfigured: boolean;
    jwtConfigured: boolean;
  };
}

export interface ApiKeyCreateResponse {
  key: ApiKeyMetadata;
  token: string;
  copyOnce: boolean;
}

export interface ApiKeyRotateResponse extends ApiKeyCreateResponse {
  previousId: string;
}

export interface JwtIssueResponse {
  token: string;
  tokenType: 'Bearer';
  expiresIn: number;
  scopes: string[];
}

export const apiKeysApi = {
  async list(): Promise<ApiKeysIndexResponse | null> {
    const response = await apiClient.get<ApiKeysIndexResponse>('/api/admin/platform/api-keys');
    return response.success && response.data ? response.data : null;
  },

  async listAudit(): Promise<ApiKeyAuditEvent[]> {
    const response = await apiClient.get<{ events: ApiKeyAuditEvent[] }>(
      '/api/admin/platform/api-keys/audit'
    );
    return response.success && response.data ? response.data.events : [];
  },

  async create(payload: {
    label: string;
    scopes: string[];
    expiresAt?: string | null;
  }): Promise<ApiResponse<ApiKeyCreateResponse>> {
    return apiClient.post<ApiKeyCreateResponse>('/api/admin/platform/api-keys', payload);
  },

  async rotate(id: string): Promise<ApiKeyRotateResponse | null> {
    const response = await apiClient.post<ApiKeyRotateResponse>(
      `/api/admin/platform/api-keys/${encodeURIComponent(id)}/rotate`
    );
    return response.success && response.data ? response.data : null;
  },

  async revoke(id: string): Promise<boolean> {
    const response = await apiClient.delete<{ revoked: boolean }>(
      `/api/admin/platform/api-keys/${encodeURIComponent(id)}`
    );
    return response.success;
  },

  async issueJwt(payload: { scopes: string[]; ttl?: number }): Promise<JwtIssueResponse | null> {
    const response = await apiClient.post<JwtIssueResponse>('/api/admin/platform/api-keys/token', payload);
    return response.success && response.data ? response.data : null;
  },
};
