import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import { applyColorScheme, clearColorScheme } from './applyColorScheme';
import { COLOR_SCHEME_IDS } from './colorSchemes';

describe('applyColorScheme', () => {
  let root: HTMLElement;

  beforeEach(() => {
    root = document.documentElement;
    clearColorScheme(root);
  });

  afterEach(() => {
    clearColorScheme(root);
    root.classList.remove('dark');
  });

  it('applies data-scheme and data-theme for each catalog id', () => {
    for (const schemeId of COLOR_SCHEME_IDS) {
      clearColorScheme(root);
      applyColorScheme(schemeId, 'light', root);

      expect(root.dataset.scheme).toBe(schemeId);
      expect(root.dataset.theme).toBe('light');
      expect(root.style.getPropertyValue('--color-primary')).not.toBe('');
    }
  });

  it('toggles dark class for dark mode', () => {
    applyColorScheme('ocean-slate', 'dark', root);
    expect(root.classList.contains('dark')).toBe(true);

    applyColorScheme('ocean-slate', 'light', root);
    expect(root.classList.contains('dark')).toBe(false);
  });

  it('falls back to indigo-classic for unknown scheme id', () => {
    applyColorScheme('unknown-scheme', 'light', root);
    expect(root.dataset.scheme).toBe('indigo-classic');
  });

  it('clears custom properties on clearColorScheme', () => {
    applyColorScheme('forest-sage', 'light', root);
    clearColorScheme(root);
    expect(root.style.getPropertyValue('--color-primary')).toBe('');
    expect(root.dataset.scheme).toBeUndefined();
  });
});
