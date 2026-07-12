// frontend/src/api/auth.ts
import apiClient from './client';
import { User, LoginRequest, LoginResponse, RegisterRequest, RegisterResponse } from './types';

export const authApi = {
  // Prihlásenie
  login: async (data: LoginRequest): Promise<LoginResponse> => {
    const response = await apiClient.post<LoginResponse>('/api/auth/login', data);
    if (response.success && response.data?.user) {
      localStorage.setItem('user', JSON.stringify(response.data.user));
      if (response.data.token) {
        apiClient.setAuthToken(response.data.token);
      }
    }
    return response.data as LoginResponse;
  },

  // Registrácia
  register: async (data: RegisterRequest): Promise<RegisterResponse> => {
    const response = await apiClient.post<RegisterResponse>('/api/auth/register', data);
    return response.data as RegisterResponse;
  },

  // Odhlásenie
  logout: async (): Promise<{ success: boolean }> => {
    const response = await apiClient.post('/api/auth/logout');
    localStorage.removeItem('user');
    apiClient.clearAuthToken();
    return response.data as { success: boolean };
  },

  // Získanie aktuálneho používateľa
  getCurrentUser: async (): Promise<User | null> => {
    const response = await apiClient.get<{ user: User }>('/api/auth/me');
    if (response.success && response.data?.user) {
      localStorage.setItem('user', JSON.stringify(response.data.user));
      return response.data.user;
    }
    return null;
  },

  // Zmena hesla
  changePassword: async (oldPassword: string, newPassword: string): Promise<{ success: boolean }> => {
    const response = await apiClient.post('/api/auth/change-password', {
      old_password: oldPassword,
      new_password: newPassword,
    });
    return response.data as { success: boolean };
  },

  // Reset hesla
  resetPassword: async (email: string): Promise<{ success: boolean; token?: string }> => {
    const response = await apiClient.post('/api/auth/reset-password', { email });
    return response.data as { success: boolean; token?: string };
  },

  // Overenie reset tokenu
  verifyResetToken: async (token: string, newPassword: string): Promise<{ success: boolean }> => {
    const response = await apiClient.post('/api/auth/verify-reset-token', {
      token,
      new_password: newPassword,
    });
    return response.data as { success: boolean };
  },

  // CSRF token
  getCsrfToken: async (key?: string): Promise<{ token: string }> => {
    const response = await apiClient.get<{ token: string }>('/api/auth/csrf-token', {
      params: { key: key || 'default' },
    });
    if (response.success && response.data?.token) {
      apiClient.setCsrfToken(response.data.token);
    }
    return response.data as { token: string };
  },

  // 2FA
  twoFactor: {
    enable: async (): Promise<{ secret: string; qr_code: string; provisioning_uri: string }> => {
      const response = await apiClient.post('/api/auth/2fa/enable');
      return response.data as { secret: string; qr_code: string; provisioning_uri: string };
    },

    disable: async (): Promise<{ success: boolean }> => {
      const response = await apiClient.post('/api/auth/2fa/disable');
      return response.data as { success: boolean };
    },

    verify: async (code: string): Promise<{ success: boolean }> => {
      const response = await apiClient.post('/api/auth/2fa/verify', { code });
      return response.data as { success: boolean };
    },

    getStatus: async (): Promise<{ enabled: boolean; verified: boolean }> => {
      const response = await apiClient.get('/api/auth/2fa/status');
      return response.data as { enabled: boolean; verified: boolean };
    },

    getQrCode: async (): Promise<{ qr_code: string; provisioning_uri: string }> => {
      const response = await apiClient.get('/api/auth/2fa/qr-code');
      return response.data as { qr_code: string; provisioning_uri: string };
    },

    verifyLogin: async (code: string): Promise<{ success: boolean; user: User }> => {
      const response = await apiClient.post('/api/auth/2fa/verify-login', { code });
      if (response.success && response.data?.user) {
        localStorage.setItem('user', JSON.stringify(response.data.user));
      }
      return response.data as { success: boolean; user: User };
    },
  },
};
