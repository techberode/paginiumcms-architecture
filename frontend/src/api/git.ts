import { apiClient } from './client';

export interface GitPublishPendingItem {
  id: string;
  resourcePath: string;
  status: string;
}

export interface GitPublishStatus {
  enabled: boolean;
  strategy: 'disabled' | 'immediate' | 'queued';
  publisher: string;
  pendingCount: number;
  pending: GitPublishPendingItem[];
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
