import { apiClient } from './client';

export interface StaticSiteStatus {
  renderMode: 'dynamic' | 'hybrid' | 'static';
  autoRebuild: boolean;
  generatedAt: number | null;
  pageCount: number;
  articleCount: number;
  writable: boolean;
  tree: string;
  publicServe: boolean;
  publicPrefix: string;
}

export interface StaticSiteRebuildResult {
  success: boolean;
  message: string;
  written?: number;
  removed?: number;
  action?: string;
}

export const staticSiteApi = {
  status: async () => apiClient.get<StaticSiteStatus>('/api/admin/static/status'),

  rebuildAll: async () =>
    apiClient.post<StaticSiteRebuildResult>('/api/admin/static/rebuild', { scope: 'all' }),
};
