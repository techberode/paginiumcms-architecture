// frontend/src/context/ThemeContext.tsx
import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { useLocation } from 'react-router-dom';
import { isAdminAppRoute } from '../utils/appRoutes';
import {
  nextAdminTheme,
  readAdminThemePreference,
  resolveAdminIsDark,
  systemPrefersDark,
  writeAdminThemePreference,
  type AdminThemePreference,
} from '../utils/adminTheme';

type Theme = AdminThemePreference;

interface ThemeContextType {
  theme: Theme;
  setTheme: (theme: Theme) => void;
  toggleTheme: () => void;
  isDark: boolean;
}

export const ThemeContext = createContext<ThemeContextType | undefined>(undefined);

export const ThemeProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const location = useLocation();
  const [theme, setThemeState] = useState<Theme>(() => readAdminThemePreference());
  const [isDark, setIsDark] = useState(() => resolveAdminIsDark(theme, systemPrefersDark()));

  const setTheme = useCallback((next: Theme) => {
    setThemeState(next);
    writeAdminThemePreference(next);
  }, []);

  const toggleTheme = useCallback(() => {
    setThemeState((current) => {
      const next = nextAdminTheme(resolveAdminIsDark(current, systemPrefersDark()));
      writeAdminThemePreference(next);
      return next;
    });
  }, []);

  useEffect(() => {
    const updateTheme = () => {
      const dark = resolveAdminIsDark(theme, systemPrefersDark());
      setIsDark(dark);
      if (isAdminAppRoute(location.pathname)) {
        document.documentElement.classList.toggle('dark', dark);
      }
    };

    updateTheme();

    if (theme !== 'system') {
      return undefined;
    }

    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    const handler = () => updateTheme();
    mediaQuery.addEventListener('change', handler);
    return () => mediaQuery.removeEventListener('change', handler);
  }, [theme, location.pathname]);

  const value = useMemo(
    () => ({ theme, setTheme, toggleTheme, isDark }),
    [theme, setTheme, toggleTheme, isDark]
  );

  return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
};

export const useTheme = () => {
  const context = useContext(ThemeContext);
  if (!context) {
    throw new Error('useTheme must be used within a ThemeProvider');
  }
  return context;
};

export default ThemeProvider;
