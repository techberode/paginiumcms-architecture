// frontend/src/api/notifications.ts
// === Notifications API (Iteration 6) ===
import apiClient from './client';

export interface ConnectorStatus {
  name: string;
  label: string;
  enabled: boolean;
}

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

export interface NotificationOverview {
  connectors: ConnectorStatus[];
  active_adapters: string[];
  fallback_email: string;
  alerts_enabled: boolean;
  analytics: AnalyticsOverview;
  top_pages: TopPage[];
}

export async function getNotificationOverview(): Promise<NotificationOverview | null> {
  const res = await apiClient.get<NotificationOverview>('/api/admin/notifications/overview');
  return res.success && res.data ? res.data : null;
}

export async function sendTestNotification(
  adapter: string,
  to?: string
): Promise<{ success: boolean; message?: string; error?: string }> {
  const res = await apiClient.post<{ message?: string }>('/api/admin/notifications/test', {
    adapter,
    to,
  });
  return {
    success: Boolean(res.success),
    message: res.message,
    error: res.error,
  };
}
