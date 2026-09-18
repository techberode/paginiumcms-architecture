import apiClient from './client';

export interface QueryIndexStatus {
  configured_driver: string;
  active_driver: string;
  activation_ready: boolean;
  json_entries: number;
  probe: {
    queryIndexDriver: { configured: string; active: string; status: string };
    capabilities: Record<string, { status: string; message: string }>;
    counts: { jsonEntries: number; sqliteEntries: number };
  };
}

export async function getQueryIndexStatus(): Promise<QueryIndexStatus | null> {
  const res = await apiClient.get<QueryIndexStatus>('/api/admin/query-index/status');
  return res.success && res.data ? res.data : null;
}

export async function rebuildQueryIndex(): Promise<boolean> {
  const res = await apiClient.post<{ entries: number; json_entries: number }>(
    '/api/admin/query-index/rebuild',
    {}
  );
  return res.success === true;
}

export async function activateQueryIndexDriver(driver: 'json' | 'sqlite'): Promise<boolean> {
  const res = await apiClient.post<{ driver: string }>('/api/admin/query-index/activate', { driver });
  return res.success === true;
}
