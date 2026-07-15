// frontend/src/api/analytics.ts
// === Analytics API (Iteration 6) ===
import apiClient from './client';

export interface AnalyticsOverview {
  period: string;
  date: string;
  visits: number;
  page_views: number;
  unique_visitors: number;
  bounce_rate: number;
  realtime_visitors: number;
}

export interface TopPage {
  uri: string;
  views: number;
}

export interface TopReferer {
  referer: string;
  visits: number;
}

export interface DeviceStats {
  desktop: number;
  mobile: number;
  tablet: number;
  unknown: number;
}

export interface AnalyticsPayload {
  overview: AnalyticsOverview;
  top_pages: TopPage[];
  top_referers: TopReferer[];
  devices: DeviceStats;
}

export interface ChartPoint {
  date: string;
  visits: number;
  page_views: number;
}

export async function getAnalyticsOverview(period = 'today'): Promise<AnalyticsPayload | null> {
  const res = await apiClient.get<AnalyticsPayload>('/api/admin/analytics/overview', {
    params: { period },
  });
  return res.success && res.data ? res.data : null;
}

export async function getAnalyticsChart(days = 30): Promise<ChartPoint[]> {
  const res = await apiClient.get<{ chart: ChartPoint[] }>('/api/admin/analytics/chart', {
    params: { days },
  });
  return res.success && res.data?.chart ? res.data.chart : [];
}
