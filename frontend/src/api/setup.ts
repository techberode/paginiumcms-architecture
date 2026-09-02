import apiClient from './client';
import type { User } from './types';

export interface SetupStatus {
  needsSetup: boolean;
  installed: boolean;
  hasUsers: boolean;
}

export interface SetupCompletePayload {
  email: string;
  password: string;
  passwordConfirm: string;
  name: string;
  siteName: string;
  language: 'sk' | 'en';
}

export interface SetupCompleteResult {
  success: boolean;
  installed: boolean;
  user?: User;
  error?: string;
  errors?: Record<string, string[]>;
}

export async function getSetupStatus(): Promise<SetupStatus | null> {
  const res = await apiClient.get<SetupStatus>('/api/setup/status');
  return res.success && res.data ? res.data : null;
}

export async function completeSetup(payload: SetupCompletePayload): Promise<SetupCompleteResult> {
  const res = await apiClient.post<{ installed: boolean; user?: User }>(
    '/api/setup/complete',
    payload
  );

  if (res.success) {
    const user = (res.user as User | undefined) ?? res.data?.user;
    return {
      success: true,
      installed: res.data?.installed ?? true,
      user,
    };
  }

  return {
    success: false,
    installed: false,
    error: res.error || res.message,
    errors: res.errors,
  };
}
