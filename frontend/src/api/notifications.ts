// frontend/src/api/notifications.ts
// === Notifications API (Iteration 6–7) ===
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

export interface ReportSchedule {
  enabled: boolean;
  interval: 'hour' | 'day' | 'week';
  time: string;
  minute?: number;
  weekday: string;
  connector: string;
  last_sent_at: string | null;
  due_now: boolean;
}

export interface LogIncidentSettings {
  notify_errors: boolean;
  notify_warnings: boolean;
  connector: string;
}

export interface NotificationOverview {
  connectors: ConnectorStatus[];
  active_adapters: string[];
  fallback_email: string;
  alerts_enabled: boolean;
  analytics: AnalyticsOverview;
  top_pages: TopPage[];
  schedule?: ReportSchedule;
  log_incidents?: LogIncidentSettings;
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

export async function sendMonitoringReport(
  force = true
): Promise<{ success: boolean; result?: Record<string, unknown>; error?: string }> {
  const res = await apiClient.post<{ result?: Record<string, unknown> }>(
    '/api/admin/notifications/report/send',
    { force }
  );
  const result = (res as ApiResponse & { result?: Record<string, unknown> }).result;

  return {
    success: Boolean(res.success),
    result,
    error: res.error || res.message || formatMonitoringReportError(result),
  };
}

function formatMonitoringReportError(result?: Record<string, unknown>): string | undefined {
  const reason = typeof result?.reason === 'string' ? result.reason : '';

  switch (reason) {
    case 'delivery_failed':
      return 'SMTP/konektor odmietol odoslanie – skontroluj host, port, heslo a či server vidí SMTP.';
    case 'connector_inactive':
      return 'Zvolený konektor reportu nie je zapnutý – Settings → Connectors + Monitoring → Report connector.';
    case 'no_connectors':
      return 'Nie je zapnutý žiadny konektor – Settings → Connectors.';
    case 'missing_recipient':
      return 'Chýba príjemca – Settings → Monitoring → alert email alebo General → admin email.';
    case 'disabled':
      return 'Plánované reporty sú vypnuté (Settings → Monitoring → Enable scheduled monitoring reports).';
    case 'not_due':
      return 'Report ešte nie je na rade – tlačidlo „Odoslať teraz“ posiela s force=true; skús obnoviť stránku.';
    default:
      return reason !== '' ? `Report neodoslaný (${reason}).` : undefined;
  }
}

export async function runMonitoringSchedule(): Promise<Record<string, unknown> | null> {
  const res = await apiClient.post<Record<string, unknown>>('/api/admin/notifications/schedule/run');
  return res.success && res.data ? res.data : null;
}
