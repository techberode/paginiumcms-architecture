import { describe, expect, it, beforeEach } from 'vitest';
import {
  ADMIN_NAV_SECTIONS_STORAGE_KEY,
  loadOpenNavSections,
  saveOpenNavSections,
} from './adminNavPersistence';

describe('adminNavPersistence', () => {
  beforeEach(() => {
    window.localStorage.clear();
  });

  it('defaults every section to open', () => {
    expect(loadOpenNavSections(['workspace', 'inbox'])).toEqual({
      workspace: true,
      inbox: true,
    });
  });

  it('overlays saved collapsed sections', () => {
    saveOpenNavSections({ workspace: false, inbox: true });
    expect(window.localStorage.getItem(ADMIN_NAV_SECTIONS_STORAGE_KEY)).toContain('workspace');
    expect(loadOpenNavSections(['workspace', 'inbox', 'platform'])).toEqual({
      workspace: false,
      inbox: true,
      platform: true,
    });
  });

  it('ignores corrupt JSON', () => {
    window.localStorage.setItem(ADMIN_NAV_SECTIONS_STORAGE_KEY, '{not-json');
    expect(loadOpenNavSections(['workspace'])).toEqual({ workspace: true });
  });
});
