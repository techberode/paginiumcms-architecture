// frontend/src/api/developer.ts
import apiClient from './client';

export interface DeveloperGateStatus {
  feature_available: boolean;
  unlocked: boolean;
  unlocked_until?: number | null;
  method?: string | null;
}

export interface DeveloperUnlockResult {
  success: boolean;
  error?: string;
  status?: DeveloperGateStatus | null;
}

export async function getDeveloperStatus(): Promise<DeveloperGateStatus | null> {
  const res = await apiClient.get<DeveloperGateStatus>('/api/admin/developer/status');
  return res.success && res.data ? res.data : null;
}

export async function unlockDeveloperMode(payload: {
  totp_code?: string;
  token?: string;
}): Promise<DeveloperUnlockResult> {
  const res = await apiClient.post<DeveloperGateStatus>('/api/admin/developer/unlock', payload);
  return {
    success: Boolean(res.success),
    error: res.error,
    status: res.data ?? null,
  };
}

export async function lockDeveloperMode(): Promise<{ success: boolean; error?: string }> {
  const res = await apiClient.post<DeveloperGateStatus>('/api/admin/developer/lock');
  return {
    success: Boolean(res.success),
    error: res.error,
  };
}
