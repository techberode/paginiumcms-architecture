// frontend/src/api/analytics.ts
// === Analytics API (Iteration 6) ===
import apiClient from './client';

export interface AnalyticsOverview {
  period: string;
  date: string;
  days?: number;
  visits: number;
  page_views: number;
  unique_visitors: number;
  bounce_rate: number;
  avg_duration_seconds?: number;
  realtime_visitors: number;
}

export interface TopArticle {
  uri: string;
  views: number;
  title: string;
}

export interface BrowserStat {
  browser: string;
  visits: number;
}

export interface GeoStat {
  country: string;
  countryCode?: string | null;
  city?: string | null;
  visits: number;
  sample_ips?: string[];
}

export interface GeoVisit {
  country: string;
  countryCode?: string | null;
  city?: string | null;
  ip_masked: string;
  requestUri: string;
  timestamp: string;
}

export interface TopPage {
  uri: string;
  views: number;
}

export interface TopReferer {
  referer: string;
  source?: string;
  domain?: string;
  type?: 'direct' | 'search' | 'social' | 'referral' | string;
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
  top_articles?: TopArticle[];
  top_referers: TopReferer[];
  devices: DeviceStats;
  browsers?: BrowserStat[];
  geo?: GeoStat[];
  geo_visits?: GeoVisit[];
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

export async function getAnalyticsRealtime(): Promise<RealtimeSnapshot | null> {
  const res = await apiClient.get<RealtimeSnapshot>('/api/admin/analytics/realtime');
  return res.success && res.data ? res.data : null;
}

export interface RealtimeSnapshot {
  window_seconds: number;
  active_visitors: number;
  active_page_views: number;
  top_active_pages: Array<{ uri: string; views: number }>;
}

export interface NotFoundPathRow {
  path: string;
  hits: number;
  lastSeen: string;
  topReferer: string | null;
}

export interface NotFoundReport {
  days: number;
  paths: NotFoundPathRow[];
}

export async function getNotFoundReport(days = 7, limit = 50): Promise<NotFoundReport | null> {
  const res = await apiClient.get<NotFoundReport>('/api/admin/analytics/not-found', {
    params: { days, limit },
  });
  return res.success && res.data ? res.data : null;
}

export async function exportNotFoundCsv(days = 7): Promise<Blob> {
  const response = await apiClient.get('/api/admin/analytics/not-found/export.csv', {
    params: { days },
    responseType: 'blob',
  });
  return response.data as Blob;
}
