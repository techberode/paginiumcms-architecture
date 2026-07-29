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
  webhookDeployEnabled?: boolean;
  githubOwner?: string;
  githubRepo?: string;
  defaultBranch?: string;
  allowDeployMain?: boolean;
  allowDeployTags?: boolean;
}

export interface SystemUpdateWebhookConfig {
  path: string;
  webhook_deploy_enabled?: boolean;
  secret_configured?: boolean;
}

export interface SystemUpdateStatus {
  app_version: string;
  demo_mode: boolean;
  git: SystemUpdateGitStatus;
  config: SystemUpdateConfig;
  job_registered: boolean;
  webhook?: SystemUpdateWebhookConfig;
  recent_runs: Array<Record<string, unknown>>;
}

export interface SystemUpdateRemoteCommit {
  sha: string;
  sha_full?: string;
  message: string;
  author?: string | null;
  date?: string | null;
  url?: string | null;
}

export interface SystemUpdateRemoteCompare {
  behind_by?: number;
  ahead_by?: number;
  status?: string;
  total_commits?: number;
  compare_head?: string | null;
  commits?: SystemUpdateRemoteCommit[];
  commits_truncated?: boolean;
}

export interface SystemUpdateRemote {
  latest_release_tag?: string | null;
  latest_release_body?: string | null;
  latest_release_url?: string | null;
  remote_commit?: string | null;
  compare?: SystemUpdateRemoteCompare | null;
  error?: string | null;
}

export interface SystemUpdateCheckResult {
  git: SystemUpdateGitStatus;
  remote: SystemUpdateRemote;
  update?: {
    status: 'current' | 'update_available' | 'unknown';
    current_version: string;
    latest_version?: string | null;
    current_tag?: string | null;
    latest_tag?: string | null;
  };
  release_notes?: string | null;
  release_url?: string | null;
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
