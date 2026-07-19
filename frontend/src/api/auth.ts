// frontend/src/api/auth.ts
// === Auth API (Iterácia 5 – session/HttpOnly cookie, bez Bearer tokenu) ===
import apiClient from './client';
import { User, LoginRequest, RegisterRequest } from './types';

export interface LoginResult {
  success: boolean;
  user?: User;
  requiresTwoFactor?: boolean;
  error?: string;
}

export interface RegisterResult {
  success: boolean;
  user?: User;
  requiresOtp?: boolean;
  challengeId?: string;
  expiresAt?: number;
  debugCode?: string;
  error?: string;
}

export const authApi = {
  login: async (data: LoginRequest): Promise<LoginResult> => {
    const res = await apiClient.post<LoginResult & { requires_two_factor?: boolean; user?: User }>(
      '/api/auth/login',
      data
    );
    if (res.success && res.user) {
      return { success: true, user: res.user as User, requiresTwoFactor: Boolean(res.requires_two_factor) };
    }
    return { success: false, error: res.error || 'Prihlásenie zlyhalo' };
  },

  register: async (data: RegisterRequest): Promise<RegisterResult> => {
    const res = await apiClient.post<{
      user?: User;
      requires_otp?: boolean;
      challenge_id?: string;
      expires_at?: number;
      debug_code?: string;
    }>('/api/auth/register', data);

    if (res.success && res.requires_otp) {
      return {
        success: true,
        requiresOtp: true,
        challengeId: res.challenge_id,
        expiresAt: res.expires_at,
        debugCode: res.debug_code,
      };
    }

    if (res.success && res.user) {
      return { success: true, user: res.user as User };
    }

    return { success: false, error: res.error || 'Registrácia zlyhala' };
  },

  verifyRegisterOtp: async (challengeId: string, code: string): Promise<RegisterResult> => {
    const res = await apiClient.post<{ user?: User }>('/api/auth/register/verify-otp', {
      challenge_id: challengeId,
      code,
    });

    if (res.success && res.user) {
      return { success: true, user: res.user as User };
    }

    return { success: false, error: res.error || 'Overenie kódu zlyhalo' };
  },

  resendRegisterOtp: async (challengeId: string): Promise<RegisterResult> => {
    const res = await apiClient.post<{ challenge_id?: string; expires_at?: number; debug_code?: string }>(
      '/api/auth/register/resend-otp',
      { challenge_id: challengeId }
    );

    if (res.success) {
      return {
        success: true,
        requiresOtp: true,
        challengeId: res.challenge_id ?? challengeId,
        expiresAt: res.expires_at,
        debugCode: res.debug_code,
      };
    }

    return { success: false, error: res.error || 'Odoslanie kódu zlyhalo' };
  },

  logout: async (): Promise<boolean> => {
    const res = await apiClient.post('/api/auth/logout');
    return Boolean(res.success);
  },

  getCurrentUser: async (): Promise<User | null> => {
    const res = await apiClient.get<{ user?: User }>('/api/auth/me');
    if (res.status === 401) {
      return null;
    }
    if (!res.success) {
      return null;
    }
    return (res.user as User | undefined) ?? res.data?.user ?? null;
  },

  /** Na rozlíšenie expirovanej session vs. dočasnej chyby siete. */
  probeSession: async (): Promise<{ user: User | null; expired: boolean }> => {
    const res = await apiClient.get<{ user?: User }>('/api/auth/me');
    if (res.status === 401) {
      return { user: null, expired: true };
    }
    if (!res.success) {
      return { user: null, expired: false };
    }
    const user = (res.user as User | undefined) ?? res.data?.user ?? null;
    return { user, expired: false };
  },

  changePassword: async (oldPassword: string, newPassword: string): Promise<boolean> => {
    const res = await apiClient.post('/api/auth/change-password', {
      old_password: oldPassword,
      new_password: newPassword,
    });
    return Boolean(res.success);
  },

  resetPassword: async (email: string): Promise<{ success: boolean; token?: string }> => {
    const res = await apiClient.post<{ token?: string }>('/api/auth/reset-password', { email });
    return { success: Boolean(res.success), token: res.token };
  },

  verifyResetToken: async (token: string, newPassword: string): Promise<boolean> => {
    const res = await apiClient.post('/api/auth/verify-reset-token', {
      token,
      new_password: newPassword,
    });
    return Boolean(res.success);
  },

  getCsrfToken: async (key?: string): Promise<string | null> => {
    const res = await apiClient.get<{ token?: string }>('/api/auth/csrf-token', {
      params: { key: key || 'default' },
    });
    const token = res.token ?? res.data?.token;
    if (token) {
      apiClient.setCsrfToken(token);
    }
    return token ?? null;
  },

  twoFactor: {
    enable: async (): Promise<{ secret: string; qr_code: string; provisioning_uri: string } | null> => {
      const res = await apiClient.post<{ secret: string; qr_code: string; provisioning_uri: string }>(
        '/api/auth/2fa/enable'
      );
      if (res.secret && res.qr_code) {
        return { secret: res.secret, qr_code: res.qr_code, provisioning_uri: res.provisioning_uri ?? '' };
      }
      return res.data ?? null;
    },

    disable: async (): Promise<boolean> => {
      const res = await apiClient.post('/api/auth/2fa/disable');
      return Boolean(res.success);
    },

    verify: async (code: string): Promise<boolean> => {
      const res = await apiClient.post('/api/auth/2fa/verify', { code });
      return Boolean(res.success);
    },

    getStatus: async (): Promise<{ enabled: boolean; verified: boolean; setupPending: boolean }> => {
      const res = await apiClient.get<{ enabled: boolean; verified: boolean; setup_pending?: boolean }>(
        '/api/auth/2fa/status'
      );
      return {
        enabled: Boolean(res.enabled ?? res.data?.enabled),
        verified: Boolean(res.verified ?? res.data?.verified),
        setupPending: Boolean(res.setup_pending ?? res.data?.setup_pending),
      };
    },

    getQrCode: async (): Promise<{ qr_code: string; provisioning_uri: string } | null> => {
      const res = await apiClient.get<{ qr_code: string; provisioning_uri: string }>('/api/auth/2fa/qr-code');
      if (res.qr_code) {
        return { qr_code: res.qr_code, provisioning_uri: res.provisioning_uri ?? '' };
      }
      return res.data ?? null;
    },

    verifyLogin: async (code: string): Promise<LoginResult> => {
      const res = await apiClient.post<{ user?: User }>('/api/auth/2fa/verify-login', { code });
      if (res.success && res.user) {
        return { success: true, user: res.user as User };
      }
      return { success: false, error: res.error || 'Neplatný TOTP kód' };
    },
  },
};
