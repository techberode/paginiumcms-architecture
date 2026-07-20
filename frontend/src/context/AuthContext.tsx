// frontend/src/context/AuthContext.tsx
// === AuthContext (Iterácia 5) ===
// Session autentifikácia cez HttpOnly cookie. Podpora 2FA „polovičného“ login stavu.
import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { User } from '../api/types';
import { authApi, LoginResult, RegisterResult } from '../api/auth';
import { debugLogProvider } from '../utils/debugLog';

export interface LoginOutcome {
  success: boolean;
  requiresTwoFactor?: boolean;
  error?: string;
}

interface AuthContextType {
  user: User | null;
  loading: boolean;
  pendingTwoFactor: boolean;
  twoFactorSetupPending: boolean;
  login: (email: string, password: string) => Promise<LoginOutcome>;
  verifyTwoFactorLogin: (code: string) => Promise<boolean>;
  logout: () => Promise<void>;
  register: (email: string, password: string, name: string) => Promise<RegisterResult>;
  verifyRegisterOtp: (challengeId: string, code: string) => Promise<RegisterResult>;
  resendRegisterOtp: (challengeId: string) => Promise<RegisterResult>;
  updateUser: (user: User) => void;
  refreshUser: () => Promise<void>;
}

export const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const [pendingTwoFactor, setPendingTwoFactor] = useState(false);
  const [twoFactorSetupPending, setTwoFactorSetupPending] = useState(false);

  const refreshUser = useCallback(async () => {
    debugLogProvider('auth', 'refresh.start');
    const probe = await authApi.probeSession();
    if (!probe.expired && probe.user === null) {
      debugLogProvider('auth', 'refresh.transient_error');
      return;
    }
    setUser(probe.user);
    if (probe.user?.twoFactorEnabled) {
      const status = await authApi.twoFactor.getStatus();
      setTwoFactorSetupPending(status.setupPending);
      // Login TOTP step only after user completed setup at least once.
      setPendingTwoFactor(!status.verified && !status.setupPending);
      debugLogProvider('auth', 'refresh.done', {
        authenticated: true,
        twoFactorEnabled: true,
        twoFactorVerified: status.verified,
        twoFactorSetupPending: status.setupPending,
        userId: probe.user.id,
      });
    } else {
      setPendingTwoFactor(false);
      setTwoFactorSetupPending(false);
      debugLogProvider('auth', 'refresh.done', {
        authenticated: Boolean(probe.user),
        twoFactorEnabled: false,
        userId: probe.user?.id ?? null,
      });
    }
  }, []);

  useEffect(() => {
    (async () => {
      try {
        await refreshUser();
      } finally {
        setLoading(false);
        debugLogProvider('auth', 'bootstrap.done', { loading: false });
      }
    })();
  }, [refreshUser]);

  useEffect(() => {
    const onTotpRequired = () => {
      setPendingTwoFactor(true);
    };
    const onAuthExpired = () => {
      void refreshUser();
    };

    window.addEventListener('paginium:totp-required', onTotpRequired);
    window.addEventListener('paginium:auth-expired', onAuthExpired);

    return () => {
      window.removeEventListener('paginium:totp-required', onTotpRequired);
      window.removeEventListener('paginium:auth-expired', onAuthExpired);
    };
  }, [refreshUser]);

  // Keep session alive during long admin edits (heartbeat alone may not run on /new routes).
  useEffect(() => {
    if (!user || pendingTwoFactor) {
      return;
    }

    const keepAliveMs = 4 * 60 * 1000;
    const timer = window.setInterval(() => {
      void refreshUser();
    }, keepAliveMs);

    return () => window.clearInterval(timer);
  }, [user, pendingTwoFactor, refreshUser]);

  const login = useCallback(async (email: string, password: string): Promise<LoginOutcome> => {
    debugLogProvider('auth', 'login.attempt', { email });
    const result: LoginResult = await authApi.login({ email, password });
    if (result.success && result.user) {
      if (!result.requiresTwoFactor) {
        const probe = await authApi.probeSessionWithRetry();
        const activeUser = probe.user ?? result.user;
        setUser(activeUser);
        if (!probe.user && probe.expired) {
          debugLogProvider('auth', 'login.session_probe_delayed', { email });
        }
        if (activeUser.twoFactorEnabled) {
          const status = await authApi.twoFactor.getStatus();
          setTwoFactorSetupPending(status.setupPending);
          setPendingTwoFactor(!status.verified && !status.setupPending);
        } else {
          setPendingTwoFactor(false);
          setTwoFactorSetupPending(false);
        }
      } else {
        setUser(result.user);
        setPendingTwoFactor(true);
        setTwoFactorSetupPending(false);
      }
      debugLogProvider('auth', 'login.success', {
        userId: result.user.id,
        requiresTwoFactor: Boolean(result.requiresTwoFactor),
      });
      return { success: true, requiresTwoFactor: result.requiresTwoFactor };
    }
    debugLogProvider('auth', 'login.failed', { email, error: result.error ?? null });
    return { success: false, error: result.error };
  }, []);

  const verifyTwoFactorLogin = useCallback(async (code: string): Promise<boolean> => {
    const result = await authApi.twoFactor.verifyLogin(code);
    if (result.success && result.user) {
      setUser(result.user);
      const probe = await authApi.probeSessionWithRetry();
      if (probe.expired && !probe.user) {
        return false;
      }
      if (probe.user) {
        setUser(probe.user);
      }
      setPendingTwoFactor(false);
      return true;
    }
    return false;
  }, []);

  const logout = useCallback(async () => {
    debugLogProvider('auth', 'logout.start', { userId: user?.id ?? null });
    await authApi.logout();
    setUser(null);
    setPendingTwoFactor(false);
    setTwoFactorSetupPending(false);
    debugLogProvider('auth', 'logout.done');
  }, [user?.id]);

  const register = useCallback(async (email: string, password: string, name: string): Promise<RegisterResult> => {
    const result = await authApi.register({ email, password, name });
    if (result.success && result.user) {
      setUser(result.user);
      setPendingTwoFactor(false);
    }
    return result;
  }, []);

  const verifyRegisterOtp = useCallback(async (challengeId: string, code: string): Promise<RegisterResult> => {
    const result = await authApi.verifyRegisterOtp(challengeId, code);
    if (result.success && result.user) {
      setUser(result.user);
      setPendingTwoFactor(false);
    }
    return result;
  }, []);

  const resendRegisterOtp = useCallback(async (challengeId: string): Promise<RegisterResult> => {
    return authApi.resendRegisterOtp(challengeId);
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
        twoFactorSetupPending,
        login,
        verifyTwoFactorLogin,
        logout,
        register,
        verifyRegisterOtp,
        resendRegisterOtp,
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
