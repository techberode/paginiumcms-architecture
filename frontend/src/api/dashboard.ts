// frontend/src/api/dashboard.ts
// === Dashboard API (Iteration 7) ===
import apiClient from './client';
import { ContentLock } from './locks';
import { ConflictRecord } from './conflicts';
import { HealthReport } from './types';
import { AnalyticsOverview, ChartPoint } from './analytics';

export interface RealtimeSnapshot {
  window_seconds: number;
  active_visitors: number;
  active_page_views: number;
  top_active_pages: Array<{ uri: string; views: number }>;
}

export interface DashboardOverview {
  locks: ContentLock[];
  locks_count: number;
  conflicts: ConflictRecord[];
  conflicts_count: number;
  health: HealthReport;
  analytics: {
    overview: AnalyticsOverview;
    chart: ChartPoint[];
    realtime: RealtimeSnapshot;
  };
  logs?: {
    hours: number;
    by_severity: Partial<Record<'debug' | 'info' | 'warning' | 'error' | 'critical', number>>;
  };
  counts?: Partial<Record<
    | 'pages'
    | 'articles'
    | 'media'
    | 'backups'
    | 'comments'
    | 'messages'
    | 'messages_unread'
    | 'trash'
    | 'users'
    | 'firewall_jails',
    number
  >>;
  storage?: {
    free_space?: string | null;
    free_space_bytes?: number | null;
  };
}

export async function getDashboardOverview(): Promise<DashboardOverview | null> {
  const res = await apiClient.get<DashboardOverview>('/api/admin/dashboard/overview');
  return res.success && res.data ? res.data : null;
}
