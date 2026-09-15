import { describe, expect, it, beforeEach } from 'vitest';
import {
  ADMIN_THEME_STORAGE_KEY,
  nextAdminTheme,
  readAdminThemePreference,
  resolveAdminIsDark,
  writeAdminThemePreference,
} from './adminTheme';

describe('adminTheme', () => {
  beforeEach(() => {
    window.localStorage.clear();
  });

  it('defaults to system', () => {
    expect(readAdminThemePreference()).toBe('system');
  });

  it('reads the dedicated admin key and ignores junk', () => {
    writeAdminThemePreference('dark');
    expect(readAdminThemePreference()).toBe('dark');
    window.localStorage.setItem(ADMIN_THEME_STORAGE_KEY, 'nope');
    expect(readAdminThemePreference()).toBe('system');
  });

  it('falls back to the legacy theme key', () => {
    window.localStorage.setItem('theme', 'light');
    expect(readAdminThemePreference()).toBe('light');
  });

  it('resolves system from the OS preference', () => {
    expect(resolveAdminIsDark('dark', false)).toBe(true);
    expect(resolveAdminIsDark('light', true)).toBe(false);
    expect(resolveAdminIsDark('system', true)).toBe(true);
    expect(resolveAdminIsDark('system', false)).toBe(false);
  });

  it('toggles to the opposite explicit mode', () => {
    expect(nextAdminTheme(true)).toBe('light');
    expect(nextAdminTheme(false)).toBe('dark');
  });
});
