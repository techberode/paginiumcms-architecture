// frontend/src/api/firewall.ts
import apiClient from './client';
import type { BulkBatchResult } from '../types/bulk';

export interface FirewallStats {
  active_jails: number;
  permanent_bans: number;
  incidents_24h: number;
  whitelist_count: number;
}

export interface FirewallIncident {
  id: string;
  ip: string;
  scenario: string;
  uri: string;
  user_agent: string;
  created_at: string;
}

export interface FirewallBan {
  ip: string;
  expires_at: number | null;
  permanent: boolean;
  score: number;
  reason: string;
  updated_at: string;
  active: boolean;
}

export interface FirewallIncidentsResponse {
  items: FirewallIncident[];
  limit: number;
  offset: number;
}

export const firewallApi = {
  stats: async (): Promise<FirewallStats | null> => {
    const res = await apiClient.get<FirewallStats>('/api/admin/firewall/stats');
    return res.success && res.data ? res.data : null;
  },

  incidents: async (limit = 50, offset = 0): Promise<FirewallIncidentsResponse | null> => {
    const res = await apiClient.get<FirewallIncidentsResponse>(
      `/api/admin/firewall/incidents?limit=${limit}&offset=${offset}`
    );
    return res.success && res.data ? res.data : null;
  },

  bans: async (all = false): Promise<FirewallBan[]> => {
    const query = all ? '?all=1' : '';
    const res = await apiClient.get<FirewallBan[]>(`/api/admin/firewall/bans${query}`);
    return res.success && Array.isArray(res.data) ? res.data : [];
  },

  createBan: async (ip: string, permanent = false, reason = 'manual'): Promise<FirewallBan | null> => {
    const res = await apiClient.post<FirewallBan>('/api/admin/firewall/bans', { ip, permanent, reason });
    return res.success && res.data ? res.data : null;
  },

  unban: async (ip: string): Promise<boolean> => {
    const encoded = encodeURIComponent(ip);
    const res = await apiClient.delete<{ ip: string }>(`/api/admin/firewall/bans/${encoded}`);
    return res.success;
  },

  whitelist: async (): Promise<string[]> => {
    const res = await apiClient.get<{ ips: string[] }>('/api/admin/firewall/whitelist');
    return res.success && res.data?.ips ? res.data.ips : [];
  },

  addWhitelist: async (ip: string): Promise<boolean> => {
    const res = await apiClient.post<{ ip: string }>('/api/admin/firewall/whitelist', { ip });
    return res.success;
  },

  removeWhitelist: async (ip: string): Promise<boolean> => {
    const encoded = encodeURIComponent(ip);
    const res = await apiClient.delete<{ ip: string }>(`/api/admin/firewall/whitelist/${encoded}`);
    return res.success;
  },

  bulkUnban: async (ids: string[]): Promise<BulkBatchResult | null> => {
    const res = await apiClient.post<BulkBatchResult>('/api/admin/firewall/bans/bulk-unban', { ids });
    return res.success && res.data ? res.data : null;
  },

  bulkRemoveWhitelist: async (ids: string[]): Promise<BulkBatchResult | null> => {
    const res = await apiClient.post<BulkBatchResult>('/api/admin/firewall/whitelist/bulk-remove', { ids });
    return res.success && res.data ? res.data : null;
  },
};
