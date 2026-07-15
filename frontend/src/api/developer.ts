// frontend/src/api/developer.ts
import apiClient from './client';

export interface DeveloperGateStatus {
  feature_available: boolean;
  unlocked: boolean;
  unlocked_until?: number | null;
  method?: string | null;
}

export async function getDeveloperStatus(): Promise<DeveloperGateStatus | null> {
  const res = await apiClient.get<DeveloperGateStatus>('/api/admin/developer/status');
  return res.success && res.data ? res.data : null;
}

export async function unlockDeveloperMode(payload: {
  totp_code?: string;
  token?: string;
}): Promise<boolean> {
  const res = await apiClient.post('/api/admin/developer/unlock', payload);
  return Boolean(res.success);
}
