import apiClient from './client';
import { normalizeDeployRef } from '../utils/deployRef';

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
  stackDir?: string;
  backendPort?: string;
  /** 0 = manual remote checks only; default 24 when unset. */
  remoteCheckIntervalHours?: number;
}

export interface SystemUpdateDeployReadiness {
  ready: boolean;
  blockers: string[];
  stack_dir?: string;
  stack_dir_configured?: boolean;
  stack_dir_accessible?: boolean;
  stack_script_executable?: boolean;
  backend_port?: string;
  deploy_script_exists?: boolean;
  app_root_configured?: boolean;
  deploy_enabled?: boolean;
  allow_deploy_tags?: boolean;
  github_token_configured?: boolean;
  git_ssh_available?: boolean;
  github_deploy_ssh_key_configured?: boolean;
  ssh_binary?: boolean;
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
  deploy_readiness?: SystemUpdateDeployReadiness;
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
  deploy_readiness?: SystemUpdateDeployReadiness;
  release_notes?: string | null;
  release_url?: string | null;
}

export interface SystemUpdateJobRunPayload {
  success?: boolean;
  message?: string;
  reason?: string;
  data?: {
    output?: string;
    ref?: string;
  };
}

export interface SystemUpdateRunResult {
  queued?: boolean;
  skipped?: boolean;
  reason?: string;
  queue_id?: string;
  ref: string;
  result?: SystemUpdateJobRunPayload | null;
}

export type SystemUpdateCredentialCheckStatus =
  | 'ok'
  | 'missing'
  | 'invalid'
  | 'unreadable'
  | 'not_required';

export type SystemUpdateGitFetchCheckStatus = 'ok' | 'failed' | 'skipped';

export interface SystemUpdateCredentialsVerify {
  checked_at: string;
  overall_ok: boolean;
  deploy_ssh_key?: {
    configured: boolean;
    path: string | null;
    env_path?: string | null;
    status?: SystemUpdateCredentialCheckStatus;
    detail: string | null;
  };
  ssh?: {
    binary: boolean;
    github_auth: boolean;
    status: SystemUpdateCredentialCheckStatus;
    detail: string | null;
  };
  github: {
    owner: string;
    repo: string;
    token: {
      status: SystemUpdateCredentialCheckStatus;
      detail: string | null;
      api_http_status: number | null;
      ssh_available: boolean;
    };
  };
  git_fetch: {
    status: SystemUpdateGitFetchCheckStatus;
    transport: 'ssh' | 'https_token' | 'https_no_token';
    detail: string | null;
    app_root: string | null;
  };
  webhook: {
    auto_deploy_enabled: boolean;
    secret: {
      status: SystemUpdateCredentialCheckStatus;
      detail: string | null;
    };
  };
}

export async function getSystemUpdateStatus(): Promise<SystemUpdateStatus | null> {
  const res = await apiClient.get<SystemUpdateStatus>('/api/admin/system/update/status');
  return res.success && res.data ? res.data : null;
}

export async function verifySystemUpdateCredentials(): Promise<{
  data: SystemUpdateCredentialsVerify | null;
  error?: string;
}> {
  const res = await apiClient.post<SystemUpdateCredentialsVerify>(
    '/api/admin/system/update/verify',
    {},
    { timeout: 120_000 }
  );
  if (res.success && res.data) {
    return { data: res.data };
  }
  return { data: null, error: res.error || res.message };
}

export async function checkSystemUpdate(): Promise<{
  data: SystemUpdateCheckResult | null;
  error?: string;
}> {
  const res = await apiClient.get<SystemUpdateCheckResult>('/api/admin/system/update/check', {
    timeout: 60_000,
  });
  if (res.success && res.data) {
    return { data: res.data };
  }
  return { data: null, error: res.error || res.message };
}

export async function runSystemUpdate(
  ref: string
): Promise<{ data: SystemUpdateRunResult | null; error?: string }> {
  const normalizedRef = normalizeDeployRef(ref);
  const res = await apiClient.post<SystemUpdateRunResult>(
    '/api/admin/system/update/run',
    { ref: normalizedRef },
    { timeout: 600_000 }
  );
  if (res.success && res.data) {
    return { data: res.data };
  }
  return { data: null, error: res.error || res.message };
}
