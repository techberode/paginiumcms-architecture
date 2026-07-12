// src/context/AuthContext.tsx
import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { authApi } from '../api/auth';
import type { User } from '../api/types';

interface AuthContextType {
  user: User | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  requiresTwoFactor: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  register: (email: string, password: string, name: string) => Promise<void>;
  changePassword: (oldPassword: string, newPassword: string) => Promise<void>;
  resetPassword: (email: string) => Promise<void>;
  verifyResetToken: (token: string, newPassword: string) => Promise<void>;
  enableTwoFactor: () => Promise<{ secret: string; qrCode: string }>;
  disableTwoFactor: () => Promise<void>;
  verifyTwoFactor: (code: string) => Promise<void>;
  verifyLoginTwoFactor: (code: string) => Promise<void>;
  getTwoFactorQRCode: () => Promise<{ qrCode: string; provisioningUri: string }>;
  getTwoFactorStatus: () => Promise<{ enabled: boolean; verified: boolean }>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [requiresTwoFactor, setRequiresTwoFactor] = useState(false);

  // 1. Spustenie overenia používateľa pri štarte aplikácie
  useEffect(() => {
    setIsLoading(true);

    authApi.getCurrentUser()
    .then((userData) => {
      if (userData) {
        setUser(userData);
        setRequiresTwoFactor(false);
        localStorage.setItem('auth_user', JSON.stringify(userData));
      } else {
        setUser(null);
        localStorage.removeItem('auth_user');
      }
    })
    .catch(() => {
      setUser(null);
      localStorage.removeItem('auth_user');
    })
    .finally(() => {
      setIsLoading(false);
    });
  }, []);

  // 2. Samostatný čistý efekt pre odhlasovací listener
  useEffect(() => {
    const handleLogout = () => {
      setUser(null);
      setRequiresTwoFactor(false);
      localStorage.removeItem('auth_user');
    };

    window.addEventListener('auth:logout', handleLogout);
    return () => window.removeEventListener('auth:logout', handleLogout);
  }, []);

  // 3. Funkcia na prihlásenie
  const login = async (email: string, password: string) => {
    const response = await authApi.login({ email, password });
    if (response.success && response.user) {
      setUser(response.user);
      setRequiresTwoFactor(false);
      localStorage.setItem('auth_user', JSON.stringify(response.user));
    } else if (response.requires_two_factor) {
      setRequiresTwoFactor(true);
    } else {
      throw new Error(response.error || 'Prihlásenie zlyhalo');
    }
  };


  const logout = async () => {
    await authApi.logout();
    setUser(null);
    setRequiresTwoFactor(false);
  };

  const register = async (email: string, password: string, name: string) => {
    const response = await authApi.register({ email, password, name });
    if (!response.success) {
      throw new Error(response.error || 'Registrácia zlyhala');
    }
  };

  const changePassword = async (oldPassword: string, newPassword: string) => {
    const response = await authApi.changePassword({ old_password: oldPassword, new_password: newPassword });
    if (!response.success) {
      throw new Error(response.error || 'Zmena hesla zlyhala');
    }
  };

  const resetPassword = async (email: string) => {
    const response = await authApi.resetPassword({ email });
    if (!response.success) {
      throw new Error(response.error || 'Reset hesla zlyhal');
    }
  };

  const verifyResetToken = async (token: string, newPassword: string) => {
    const response = await authApi.verifyResetToken({ token, new_password: newPassword });
    if (!response.success) {
      throw new Error(response.error || 'Overenie tokenu zlyhalo');
    }
  };

  const enableTwoFactor = async () => {
    const response = await authApi.enableTwoFactor();
    if (!response.success) {
      throw new Error(response.error || 'Aktivácia 2FA zlyhala');
    }
    // Aktualizácia používateľa
    if (user) {
      const updatedUser = await authApi.getCurrentUser();
      if (updatedUser) setUser(updatedUser);
    }
    return { secret: response.secret, qrCode: response.qr_code };
  };

  const disableTwoFactor = async () => {
    const response = await authApi.disableTwoFactor();
    if (!response.success) {
      throw new Error(response.error || 'Deaktivácia 2FA zlyhala');
    }
    if (user) {
      const updatedUser = await authApi.getCurrentUser();
      if (updatedUser) setUser(updatedUser);
    }
  };

  const verifyTwoFactor = async (code: string) => {
    const response = await authApi.verifyTwoFactor({ code });
    if (!response.success) {
      throw new Error(response.error || 'Overenie TOTP kódu zlyhalo');
    }
    if (user) {
      const updatedUser = await authApi.getCurrentUser();
      if (updatedUser) setUser(updatedUser);
    }
  };

  const verifyLoginTwoFactor = async (code: string) => {
    const response = await authApi.verifyLoginTwoFactor({ code });
    if (!response.success) {
      throw new Error(response.error || 'Overenie TOTP kódu zlyhalo');
    }
    if (response.user) {
      setUser(response.user);
      setRequiresTwoFactor(false);
    }
  };

  const getTwoFactorQRCode = async () => {
    const response = await authApi.getTwoFactorQRCode();
    return { qrCode: response.qr_code, provisioningUri: response.provisioning_uri };
  };

  const getTwoFactorStatus = async () => {
    return authApi.getTwoFactorStatus();
  };

  const value = {
    user,
    isLoading,
    isAuthenticated: !!user,
    requiresTwoFactor,
    login,
    logout,
    register,
    changePassword,
    resetPassword,
    verifyResetToken,
    enableTwoFactor,
    disableTwoFactor,
    verifyTwoFactor,
    verifyLoginTwoFactor,
    getTwoFactorQRCode,
    getTwoFactorStatus,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};
