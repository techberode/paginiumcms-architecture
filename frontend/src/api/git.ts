import { apiClient } from './client';

export interface GitPublishStatus {
  enabled: boolean;
  strategy: 'disabled' | 'immediate' | 'queued';
  publisher: string;
  pendingCount: number;
  pending: Array<Record<string, unknown>>;
  publisherStatus: Record<string, unknown>;
}

export interface GitPublishPreview {
  strategy: string;
  pathCount: number;
  paths: string[];
  message: string;
}

export const gitApi = {
  status: async () => apiClient.get<GitPublishStatus>('/api/admin/git/status'),

  preview: async () => apiClient.get<GitPublishPreview>('/api/admin/git/publish/preview'),

  publish: async () => apiClient.post<Record<string, unknown>>('/api/admin/git/publish'),

  retry: async (jobId: string) =>
    apiClient.post<Record<string, unknown>>(`/api/admin/git/publish/${encodeURIComponent(jobId)}/retry`),
};
