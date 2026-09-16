import { describe, expect, it } from 'vitest';
import {
  ADMIN_NAV_ANALYTICS_ITEM,
  ADMIN_NAV_PRIMARY_ITEM,
  ADMIN_NAV_SECTIONS,
} from '../config/adminNavSections';
import { isAdminAppRoute } from './appRoutes';

const NAV_HREFS = [
  ADMIN_NAV_PRIMARY_ITEM.href,
  ADMIN_NAV_ANALYTICS_ITEM.href,
  ...ADMIN_NAV_SECTIONS.flatMap((section) => section.items.map((item) => item.href)),
];

describe('isAdminAppRoute', () => {
  it('treats every sidebar href as an admin route (theme toggle + no public scheme)', () => {
    for (const href of NAV_HREFS) {
      expect(isAdminAppRoute(href), href).toBe(true);
    }
  });

  it('covers workspace, inbox, and legacy /platform/* bookmarks', () => {
    expect(isAdminAppRoute('/categories')).toBe(true);
    expect(isAdminAppRoute('/gallery')).toBe(true);
    expect(isAdminAppRoute('/mail')).toBe(true);
    expect(isAdminAppRoute('/kanban')).toBe(true);
    expect(isAdminAppRoute('/platform/mail')).toBe(true);
    expect(isAdminAppRoute('/preview/home')).toBe(true);
    expect(isAdminAppRoute('/developer/logs')).toBe(true);
    expect(isAdminAppRoute('/account')).toBe(true);
    expect(isAdminAppRoute('/account/security')).toBe(true);
  });

  it('leaves the public site to the public color scheme', () => {
    expect(isAdminAppRoute('/')).toBe(false);
    expect(isAdminAppRoute('/blog')).toBe(false);
    expect(isAdminAppRoute('/blog/hello')).toBe(false);
    expect(isAdminAppRoute('/features')).toBe(false);
    expect(isAdminAppRoute('/login')).toBe(false);
  });
});
