export const ADMIN_THEME_STORAGE_KEY = 'paginium.admin.theme';
/** Legacy key used by ThemeContext before It.93g. */
const LEGACY_THEME_STORAGE_KEY = 'theme';

export type AdminThemePreference = 'light' | 'dark' | 'system';

export function isAdminThemePreference(value: unknown): value is AdminThemePreference {
  return value === 'light' || value === 'dark' || value === 'system';
}

export function readAdminThemePreference(): AdminThemePreference {
  if (typeof window === 'undefined') {
    return 'system';
  }

  const stored =
    window.localStorage.getItem(ADMIN_THEME_STORAGE_KEY) ??
    window.localStorage.getItem(LEGACY_THEME_STORAGE_KEY);

  return isAdminThemePreference(stored) ? stored : 'system';
}

export function writeAdminThemePreference(theme: AdminThemePreference): void {
  if (typeof window === 'undefined') {
    return;
  }
  window.localStorage.setItem(ADMIN_THEME_STORAGE_KEY, theme);
}

export function systemPrefersDark(): boolean {
  if (typeof window === 'undefined') {
    return false;
  }
  return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

export function resolveAdminIsDark(theme: AdminThemePreference, prefersDark: boolean): boolean {
  if (theme === 'dark') {
    return true;
  }
  if (theme === 'light') {
    return false;
  }
  return prefersDark;
}

export function nextAdminTheme(isDark: boolean): 'light' | 'dark' {
  return isDark ? 'light' : 'dark';
}
