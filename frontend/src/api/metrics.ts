// frontend/src/api/metrics.ts
import apiClient from './client';

export interface ApmConfig {
  enabled: boolean;
  sample_rate: number;
  latency_ms_warning: number;
  latency_ms_critical: number;
  breach_count: number;
  window_minutes: number;
  remediation_mode: string;
}

export interface ApmSummary {
  sample_count: number;
  error_rate: number;
  p50_ms: number | null;
  p95_ms: number | null;
  p99_ms: number | null;
  cache_hits: number;
  cache_misses: number;
  storage_reads: number;
  storage_writes: number;
  by_route: Array<{ route: string; count: number; p95_ms: number | null }>;
}

export interface ApmBreach {
  id: string;
  route: string;
  severity: string;
  duration_ms: number;
  opened_at: string;
  recommendations?: string[];
}

export interface ApmOverview {
  config: ApmConfig;
  summary: ApmSummary;
  recent_breaches: ApmBreach[];
  host_metrics_note: string;
}

export async function getApmOverview(): Promise<ApmOverview | null> {
  const res = await apiClient.get<ApmOverview>('/api/admin/metrics/apm');
  return res.success && res.data ? res.data : null;
}

export async function clearApmSamples(): Promise<boolean> {
  const res = await apiClient.post<{ cleared: boolean }>('/api/admin/metrics/apm/clear', {});
  return res.success === true;
}
