// frontend/src/api/logs.ts
import apiClient from './client';
import type { BulkBatchResult } from '../types/bulk';

export type LogSeverity = 'debug' | 'info' | 'warning' | 'error' | 'critical';
export type LogArchivedFilter = 'active' | 'archived' | 'all';

export interface LogEntry {
  id: string;
  timestamp: string;
  severity: LogSeverity;
  category: string;
  message: string;
  display_message?: string;
  userId?: string | null;
  ip?: string | null;
  context?: Record<string, unknown> | null;
  file?: string | null;
  line?: number | null;
  source?: string;
  archived?: boolean;
  archivedAt?: string;
}

export interface LogStats {
  hours: number;
  by_severity: Record<LogSeverity, number>;
  sources: string[];
  severities: LogSeverity[];
}

export interface LogListResponse {
  items: LogEntry[];
  limit: number;
  offset: number;
  total: number;
  sources: string[];
}

export const logsApi = {
  stats: async (hours = 24): Promise<LogStats | null> => {
    const res = await apiClient.get<LogStats>(`/api/admin/logs/stats?hours=${hours}`);
    return res.success && res.data ? res.data : null;
  },

  list: async (params: {
    limit?: number;
    offset?: number;
    severity?: LogSeverity | '';
    source?: string;
    category?: string;
    search?: string;
    archived?: LogArchivedFilter;
  } = {}): Promise<LogListResponse | null> => {
    const query = new URLSearchParams();
    if (params.limit) query.set('limit', String(params.limit));
    if (params.offset) query.set('offset', String(params.offset));
    if (params.severity) query.set('severity', params.severity);
    if (params.source) query.set('source', params.source);
    if (params.category) query.set('category', params.category);
    if (params.search) query.set('search', params.search);
    if (params.archived) query.set('archived', params.archived);

    const res = await apiClient.get<LogListResponse>(`/api/admin/logs?${query.toString()}`);
    return res.success && res.data ? res.data : null;
  },

  purge: async (): Promise<number | null> => {
    const res = await apiClient.post<{ removed_files: number }>('/api/admin/logs/purge');
    return res.success && res.data ? res.data.removed_files : null;
  },

  bulkAction: async (ids: string[], action: 'delete' | 'archive'): Promise<BulkBatchResult | null> => {
    const res = await apiClient.post<BulkBatchResult>('/api/admin/logs/bulk', { ids, action });
    return res.success && res.data ? res.data : null;
  },

  deleteAll: async (): Promise<{ deleted_files: number; deleted_entries: number } | null> => {
    const res = await apiClient.post<{ deleted_files: number; deleted_entries: number }>(
      '/api/admin/logs/delete-all'
    );
    return res.success && res.data ? res.data : null;
  },
};

export const LOG_SEVERITY_LABELS: Record<LogSeverity, string> = {
  debug: 'Debug',
  info: 'Info',
  warning: 'Warning',
  error: 'Error',
  critical: 'Critical',
};

export const LOG_SEVERITY_COLORS: Record<LogSeverity, string> = {
  debug: 'bg-slate-100 text-slate-700',
  info: 'bg-blue-100 text-blue-800',
  warning: 'bg-amber-100 text-amber-800',
  error: 'bg-orange-100 text-orange-800',
  critical: 'bg-red-100 text-red-800',
};
