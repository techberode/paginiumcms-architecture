import apiClient, { type ApiResponse } from './client';

export interface WebhookMetadata {
  id: string;
  label: string;
  url: string;
  events: string[];
  enabled: boolean;
  createdAt: string;
  updatedAt: string | null;
  createdBy: string;
}

export interface WebhookDelivery {
  id: string;
  webhookId: string;
  event: string;
  status: 'pending' | 'success' | 'dead';
  attempt: number;
  maxAttempts: number;
  httpStatus: number | null;
  lastError: string;
  createdAt: string;
  updatedAt: string | null;
  deliveredAt: string | null;
  nextRetryAt: string | null;
}

export interface WebhooksIndexResponse {
  webhooks: WebhookMetadata[];
  availableEvents: string[];
  config?: {
    encryptionEnabled: boolean;
  };
}

export interface WebhookCreateResponse {
  webhook: WebhookMetadata;
  secret: string;
  copyOnce: boolean;
}

export interface WebhookTestResponse {
  deliveryId: string;
  delivery: {
    id: string;
    status: string;
    httpStatus: number | null;
    lastError: string;
  } | null;
}

export const webhooksApi = {
  list(): Promise<WebhooksIndexResponse | null> {
    return apiClient.get<WebhooksIndexResponse>('/api/admin/platform/webhooks').then((r) => r.data ?? null);
  },

  create(payload: { label: string; url: string; events: string[] }): Promise<ApiResponse<WebhookCreateResponse>> {
    return apiClient.post<WebhookCreateResponse>('/api/admin/platform/webhooks', payload);
  },

  update(
    id: string,
    payload: Partial<{ label: string; url: string; events: string[]; enabled: boolean }>
  ): Promise<ApiResponse<{ webhook: WebhookMetadata }>> {
    return apiClient.put<{ webhook: WebhookMetadata }>(`/api/admin/platform/webhooks/${encodeURIComponent(id)}`, payload);
  },

  remove(id: string): Promise<boolean> {
    return apiClient
      .delete<{ deleted: boolean }>(`/api/admin/platform/webhooks/${encodeURIComponent(id)}`)
      .then((r) => r.success === true);
  },

  rotate(id: string): Promise<ApiResponse<WebhookCreateResponse>> {
    return apiClient.post<WebhookCreateResponse>(`/api/admin/platform/webhooks/${encodeURIComponent(id)}/rotate`, {});
  },

  test(id: string): Promise<ApiResponse<WebhookTestResponse>> {
    return apiClient.post<WebhookTestResponse>(`/api/admin/platform/webhooks/${encodeURIComponent(id)}/test`, {});
  },

  deliveries(id: string): Promise<WebhookDelivery[]> {
    return apiClient
      .get<{ deliveries: WebhookDelivery[] }>(`/api/admin/platform/webhooks/${encodeURIComponent(id)}/deliveries`)
      .then((r) => r.data?.deliveries ?? []);
  },
};
