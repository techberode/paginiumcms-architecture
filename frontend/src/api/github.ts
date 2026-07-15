// frontend/src/api/github.ts
import apiClient from './client';

export interface GitHubStatus {
  enabled: boolean;
  repo: string;
  branch: string;
  auto_sync: boolean;
  configured: boolean;
}

export interface GitHubSyncResult {
  success: boolean;
  error?: string;
  exported?: number;
  imported?: number;
  files?: number;
  errors?: string[];
}

export async function getGitHubStatus(): Promise<GitHubStatus | null> {
  const res = await apiClient.get<GitHubStatus>('/api/admin/github/status');
  return res.success && res.data ? res.data : null;
}

export async function exportToGitHub(message?: string): Promise<GitHubSyncResult> {
  const res = await apiClient.post<GitHubSyncResult>('/api/admin/github/export', { message });
  return (res.data as GitHubSyncResult) ?? { success: false, error: res.error };
}

export async function importFromGitHub(): Promise<GitHubSyncResult> {
  const res = await apiClient.post<GitHubSyncResult>('/api/admin/github/import');
  return (res.data as GitHubSyncResult) ?? { success: false, error: res.error };
}

export async function syncGitHub(message?: string): Promise<GitHubSyncResult> {
  const res = await apiClient.post<GitHubSyncResult>('/api/admin/github/sync', { message });
  return (res.data as GitHubSyncResult) ?? { success: false, error: res.error };
}

export async function setGitHubAutoSync(enabled: boolean): Promise<boolean> {
  const res = await apiClient.put('/api/admin/github/auto-sync', { enabled });
  return res.success;
}
