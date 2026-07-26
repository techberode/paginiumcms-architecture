import { useCallback, useEffect, useMemo, useState } from 'react';
import { useLocation } from 'react-router-dom';
import { useSettingsContext } from '../context/SettingsContext';
import {
  type AppearanceMode,
  DEFAULT_COLOR_SCHEME_ID,
  isColorSchemeId,
  type ResolvedTheme,
  resolveThemeMode,
} from '../theme/colorSchemes';
import { applyColorScheme, clearColorScheme } from '../theme/applyColorScheme';
import { isAdminAppRoute } from '../utils/appRoutes';

export const PUBLIC_THEME_STORAGE_KEY = 'paginium-public-theme';

export interface PublicAppearanceSettings {
  colorScheme: string;
  mode: AppearanceMode;
  allowUserToggle: boolean;
}

const DEFAULT_APPEARANCE: PublicAppearanceSettings = {
  colorScheme: DEFAULT_COLOR_SCHEME_ID,
  mode: 'system',
  allowUserToggle: true,
};

function readVisitorMode(): AppearanceMode | null {
  if (typeof window === 'undefined') {
    return null;
  }
  const saved = window.localStorage.getItem(PUBLIC_THEME_STORAGE_KEY);
  if (saved === 'light' || saved === 'dark') {
    return saved;
  }
  return null;
}

function readSystemPrefersDark(): boolean {
  if (typeof window === 'undefined') {
    return false;
  }
  return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

export interface UsePublicAppearanceOptions {
  enabled?: boolean;
}

export function usePublicAppearance(options: UsePublicAppearanceOptions = {}) {
  const { enabled = true } = options;
  const location = useLocation();
  const { settings } = useSettingsContext();
  const [visitorMode, setVisitorModeState] = useState<AppearanceMode | null>(() => readVisitorMode());
  const [systemPrefersDark, setSystemPrefersDark] = useState(readSystemPrefersDark);

  const appearance = useMemo<PublicAppearanceSettings>(() => {
    const fromApi = settings.appearance;
    return {
      colorScheme: fromApi?.colorScheme ?? DEFAULT_APPEARANCE.colorScheme,
      mode: fromApi?.mode ?? DEFAULT_APPEARANCE.mode,
      allowUserToggle: fromApi?.allowUserToggle ?? DEFAULT_APPEARANCE.allowUserToggle,
    };
  }, [settings.appearance]);

  const siteMode = appearance.mode;
  const colorSchemeId = isColorSchemeId(appearance.colorScheme)
    ? appearance.colorScheme
    : DEFAULT_COLOR_SCHEME_ID;

  const effectiveMode: AppearanceMode = useMemo(() => {
    if (appearance.allowUserToggle && visitorMode) {
      return visitorMode;
    }
    return siteMode;
  }, [appearance.allowUserToggle, siteMode, visitorMode]);

  const resolvedTheme: ResolvedTheme = useMemo(() => {
    if (effectiveMode === 'light' || effectiveMode === 'dark') {
      return effectiveMode;
    }
    return systemPrefersDark ? 'dark' : 'light';
  }, [effectiveMode, systemPrefersDark]);

  useEffect(() => {
    if (effectiveMode !== 'system') {
      return undefined;
    }

    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    const handler = () => setSystemPrefersDark(mediaQuery.matches);
    handler();
    mediaQuery.addEventListener('change', handler);
    return () => mediaQuery.removeEventListener('change', handler);
  }, [effectiveMode]);

  useEffect(() => {
    const shouldApply = enabled && !isAdminAppRoute(location.pathname);
    if (!shouldApply) {
      clearColorScheme();
      return undefined;
    }

    applyColorScheme(colorSchemeId, effectiveMode);
    return undefined;
  }, [colorSchemeId, effectiveMode, enabled, location.pathname]);

  const setVisitorMode = useCallback(
    (mode: AppearanceMode | null) => {
      if (mode === 'light' || mode === 'dark') {
        window.localStorage.setItem(PUBLIC_THEME_STORAGE_KEY, mode);
        setVisitorModeState(mode);
        return;
      }
      window.localStorage.removeItem(PUBLIC_THEME_STORAGE_KEY);
      setVisitorModeState(null);
    },
    []
  );

  const toggleVisitorTheme = useCallback(() => {
    const current = resolveThemeMode(effectiveMode);
    const next: AppearanceMode = current === 'dark' ? 'light' : 'dark';
    setVisitorMode(next);
  }, [effectiveMode, setVisitorMode]);

  return {
    colorSchemeId,
    siteMode,
    effectiveMode,
    resolvedTheme,
    allowUserToggle: appearance.allowUserToggle,
    visitorMode,
    setVisitorMode,
    toggleVisitorTheme,
  };
}
