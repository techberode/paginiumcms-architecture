// frontend/src/context/AuthContext.tsx
// === AuthContext (Iterácia 5) ===
// Session autentifikácia cez HttpOnly cookie. Podpora 2FA „polovičného“ login stavu.
import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { User } from '../api/types';
import { authApi, LoginResult } from '../api/auth';

export interface LoginOutcome {
  success: boolean;
  requiresTwoFactor?: boolean;
}

interface AuthContextType {
  user: User | null;
  loading: boolean;
  pendingTwoFactor: boolean;
  login: (email: string, password: string) => Promise<LoginOutcome>;
  verifyTwoFactorLogin: (code: string) => Promise<boolean>;
  logout: () => Promise<void>;
  register: (email: string, password: string, name: string) => Promise<boolean>;
  updateUser: (user: User) => void;
  refreshUser: () => Promise<void>;
}

export const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const [pendingTwoFactor, setPendingTwoFactor] = useState(false);

  const refreshUser = useCallback(async () => {
    const userData = await authApi.getCurrentUser();
    setUser(userData);
    if (userData?.twoFactorEnabled) {
      const status = await authApi.twoFactor.getStatus();
      setPendingTwoFactor(!status.verified);
    } else {
      setPendingTwoFactor(false);
    }
  }, []);

  useEffect(() => {
    (async () => {
      try {
        await refreshUser();
      } finally {
        setLoading(false);
      }
    })();
  }, [refreshUser]);

  const login = useCallback(async (email: string, password: string): Promise<LoginOutcome> => {
    const result: LoginResult = await authApi.login({ email, password });
    if (result.success && result.user) {
      setUser(result.user);
      setPendingTwoFactor(Boolean(result.requiresTwoFactor));
      return { success: true, requiresTwoFactor: result.requiresTwoFactor };
    }
    return { success: false };
  }, []);

  const verifyTwoFactorLogin = useCallback(async (code: string): Promise<boolean> => {
    const result = await authApi.twoFactor.verifyLogin(code);
    if (result.success && result.user) {
      setUser(result.user);
      setPendingTwoFactor(false);
      return true;
    }
    return false;
  }, []);

  const logout = useCallback(async () => {
    await authApi.logout();
    setUser(null);
    setPendingTwoFactor(false);
  }, []);

  const register = useCallback(async (email: string, password: string, name: string): Promise<boolean> => {
    const result = await authApi.register({ email, password, name });
    if (result.success && result.user) {
      setUser(result.user);
      setPendingTwoFactor(false);
      return true;
    }
    return false;
  }, []);

  const updateUser = useCallback((updatedUser: User) => {
    setUser(updatedUser);
  }, []);

  return (
    <AuthContext.Provider
      value={{
        user,
        loading,
        pendingTwoFactor,
        login,
        verifyTwoFactorLogin,
        logout,
        register,
        updateUser,
        refreshUser,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
};

export const useAuthContext = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuthContext must be used within an AuthProvider');
  }
  return context;
};

export default AuthProvider;
