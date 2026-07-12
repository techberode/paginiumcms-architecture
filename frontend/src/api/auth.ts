// src/api/auth.ts
import api from './client';
import type {
  User,
  AuthResponse,
  LoginRequest,
  RegisterRequest,
  ChangePasswordRequest,
  ResetPasswordRequest,
  VerifyResetTokenRequest,
  TwoFactorEnableResponse,
  TwoFactorVerifyRequest,
} from './types';

export const authApi = {
  // Prihlásenie
  async login(data: LoginRequest): Promise<AuthResponse> {
    const response = await api.post<AuthResponse>('/auth/login', data); // Odstránené /api
    if (response.success && response.user) {
      localStorage.setItem('auth_user', JSON.stringify(response.user));
    }
    return response;
  },

  // Registrácia
  async register(data: RegisterRequest): Promise<AuthResponse> {
    return api.post<AuthResponse>('/auth/register', data); // Odstránené /api
  },

  // Odhlásenie
  async logout(): Promise<AuthResponse> {
    const response = await api.post<AuthResponse>('/auth/logout'); // Odstránené /api
    localStorage.removeItem('auth_user');
    return response;
  },

  // Získanie aktuálneho používateľa
  async getCurrentUser(): Promise<User | null> {
    try {
      const response = await api.get<{ user: User }>('/auth/me'); // Odstránené /api
      const user = response.user;
      localStorage.setItem('auth_user', JSON.stringify(user));
      return user;
    } catch {
      localStorage.removeItem('auth_user');
      return null;
    }
  },

  // Zmena hesla
  async changePassword(data: ChangePasswordRequest): Promise<AuthResponse> {
    return api.post<AuthResponse>('/auth/change-password', data); // Odstránené /api
  },

  // Reset hesla
  async resetPassword(data: ResetPasswordRequest): Promise<AuthResponse> {
    return api.post<AuthResponse>('/auth/reset-password', data); // Odstránené /api
  },

  // Overenie reset tokenu
  async verifyResetToken(data: VerifyResetTokenRequest): Promise<AuthResponse> {
    return api.post<AuthResponse>('/auth/verify-reset-token', data); // Odstránené /api
  },

  // CSRF token
  async getCsrfToken(key?: string): Promise<string> {
    return api.getCsrfToken(key);
  },

  // 2FA – aktivácia
  async enableTwoFactor(): Promise<TwoFactorEnableResponse> {
    return api.post<TwoFactorEnableResponse>('/auth/2fa/enable'); // Odstránené /api
  },

  // 2FA – deaktivácia
  async disableTwoFactor(): Promise<AuthResponse> {
    return api.post<AuthResponse>('/auth/2fa/disable'); // Odstránené /api
  },

  // 2FA – overenie
  async verifyTwoFactor(data: TwoFactorVerifyRequest): Promise<AuthResponse> {
    return api.post<AuthResponse>('/auth/2fa/verify', data); // Odstránené /api
  },

  // 2FA – overenie pri prihlásení
  async verifyLoginTwoFactor(data: TwoFactorVerifyRequest): Promise<AuthResponse> {
    return api.post<AuthResponse>('/auth/2fa/verify-login', data); // Odstránené /api
  },

  // 2FA – QR kód
  async getTwoFactorQRCode(): Promise<{ qr_code: string; provisioning_uri: string }> {
    return api.get('/auth/2fa/qr-code'); // Odstránené /api
  },

  // 2FA – stav
  async getTwoFactorStatus(): Promise<{ enabled: boolean; verified: boolean }> {
    return api.get('/auth/2fa/status'); // Odstránené /api
  },

  // Načítanie používateľa z localStorage
  getStoredUser(): User | null {
    const data = localStorage.getItem('auth_user');
    if (data) {
      try {
        return JSON.parse(data);
      } catch {
        return null;
      }
    }
    return null;
  },
};
