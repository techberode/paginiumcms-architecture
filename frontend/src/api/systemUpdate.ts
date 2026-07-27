import apiClient from './client';

export interface SystemUpdateGitStatus {
  available: boolean;
  describe?: string | null;
  commit?: string | null;
  branch?: string | null;
  dirty?: boolean;
}

export interface SystemUpdateConfig {
  deployEnabled?: boolean;
  githubOwner?: string;
  githubRepo?: string;
  defaultBranch?: string;
  allowDeployMain?: boolean;
  allowDeployTags?: boolean;
}

export interface SystemUpdateStatus {
  app_version: string;
  demo_mode: boolean;
  git: SystemUpdateGitStatus;
  config: SystemUpdateConfig;
  job_registered: boolean;
  recent_runs: Array<Record<string, unknown>>;
}

export interface SystemUpdateCheckResult {
  git: SystemUpdateGitStatus;
  remote: Record<string, unknown>;
}

export interface SystemUpdateRunResult {
  queued: boolean;
  queue_id?: string;
  ref: string;
  result?: Record<string, unknown> | null;
}

export async function getSystemUpdateStatus(): Promise<SystemUpdateStatus | null> {
  const res = await apiClient.get<SystemUpdateStatus>('/api/admin/system/update/status');
  return res.success && res.data ? res.data : null;
}

export async function checkSystemUpdate(): Promise<SystemUpdateCheckResult | null> {
  const res = await apiClient.post<SystemUpdateCheckResult>('/api/admin/system/update/check', {});
  return res.success && res.data ? res.data : null;
}

export async function runSystemUpdate(
  ref: string
): Promise<{ data: SystemUpdateRunResult | null; error?: string }> {
  const res = await apiClient.post<SystemUpdateRunResult>(
    '/api/admin/system/update/run',
    { ref },
    { timeout: 600_000 }
  );
  if (res.success && res.data) {
    return { data: res.data };
  }
  return { data: null, error: res.error || res.message };
}
