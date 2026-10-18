import apiClient from './client';
import type { User } from './types';

export interface SetupStatus {
  needsSetup: boolean;
  installed: boolean;
  hasUsers: boolean;
}

export type SetupPreflightCheckStatus = 'pass' | 'fail' | 'warn' | 'info';
export type SetupPreflightSeverity = 'hard' | 'soft' | 'info';

export interface SetupPreflightCheck {
  id: string;
  status: SetupPreflightCheckStatus;
  severity: SetupPreflightSeverity;
  current: string | null;
  required: string | null;
  installSteps: string[];
}

export interface SetupPreflight {
  ready: boolean;
  hardBlockers: number;
  softWarnings: number;
  checks: SetupPreflightCheck[];
}

export interface SetupCompletePayload {
  email: string;
  password: string;
  passwordConfirm: string;
  name: string;
  siteName: string;
  language: 'sk' | 'en';
  backendPort?: string;
  storageDriver?: 'local';
  /** Set from VITE_SETUP_TOKEN when production requires SETUP_TOKEN on the server. */
  setupToken?: string;
}

export interface SetupCompleteResult {
  success: boolean;
  installed: boolean;
  loginRequired?: boolean;
  redirectTo?: string;
  user?: User;
  error?: string;
  errors?: Record<string, string[]>;
}

const setupTokenHeader = (): Record<string, string> => {
  const token = import.meta.env.VITE_SETUP_TOKEN?.trim();
  return token ? { 'X-Setup-Token': token } : {};
};

const withSetupToken = <T extends SetupCompletePayload>(payload: T): T => {
  const token = import.meta.env.VITE_SETUP_TOKEN?.trim();
  if (!token) {
    return payload;
  }

  return { ...payload, setupToken: token };
};

export async function getSetupStatus(): Promise<SetupStatus | null> {
  const res = await apiClient.get<SetupStatus>('/api/setup/status', { headers: setupTokenHeader() });
  return res.success && res.data ? res.data : null;
}

export async function getSetupPreflight(): Promise<SetupPreflight | null> {
  const res = await apiClient.get<SetupPreflight>('/api/setup/preflight', { headers: setupTokenHeader() });
  return res.success && res.data ? res.data : null;
}

export async function completeSetup(payload: SetupCompletePayload): Promise<SetupCompleteResult> {
  const res = await apiClient.post<{
    installed: boolean;
    loginRequired?: boolean;
    redirectTo?: string;
    user?: User;
  }>('/api/setup/complete', withSetupToken(payload), { headers: setupTokenHeader() });

  if (res.success) {
    return {
      success: true,
      installed: res.data?.installed ?? res.installed ?? true,
      loginRequired: res.data?.loginRequired ?? res.loginRequired ?? true,
      redirectTo: res.data?.redirectTo ?? res.redirectTo ?? '/login',
      user: (res.user as User | undefined) ?? res.data?.user,
    };
  }

  return {
    success: false,
    installed: false,
    error: res.error || res.message,
    errors: res.errors,
  };
}
