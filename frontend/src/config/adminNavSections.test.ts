import { describe, expect, it } from 'vitest';
import { ADMIN_NAV_ANALYTICS_ITEM, ADMIN_NAV_PRIMARY_ITEM, ADMIN_NAV_SECTIONS } from './adminNavSections';
import { repositoryDoc } from './repositoryDocs';
import { isAdminAppRoute } from '../utils/appRoutes';

/** Routes registered under AdminShell in App.tsx (keep in sync when adding nav items). */
const ADMIN_ROUTES = new Set([
  '/dashboard',
  '/analytics',
  '/pages',
  '/articles',
  '/categories',
  '/editorial-calendar',
  '/project-planner',
  '/time-tracker',
  '/media',
  '/gallery',
  '/navigation',
  '/comments',
  '/messages',
  '/newsletter',
  '/settings',
  '/translations',
  '/users',
  '/teams',
  '/events',
  '/kanban',
  '/mail',
  '/api-keys',
  '/redirects',
  '/webhooks',
  '/shortcodes',
  '/widgets',
  '/snippets',
  '/notifications',
  '/scheduler',
  '/update',
  '/origin',
  '/account',
  '/account/public',
  '/account/security',
  '/account/preferences',
  '/code-editor',
  '/blueprints',
  '/extensions',
  '/themes',
  '/demo',
  '/firewall',
  '/logs',
  '/audit',
  '/security-audit',
  '/roles',
  '/backups',
  '/trash',
  '/github',
]);

describe('admin navigation wiring', () => {
  it('maps every sidebar href to a registered admin route', () => {
    const hrefs = [
      ADMIN_NAV_PRIMARY_ITEM.href,
      ADMIN_NAV_ANALYTICS_ITEM.href,
      ...ADMIN_NAV_SECTIONS.flatMap((section) => section.items.map((item) => item.href)),
    ];

    for (const href of hrefs) {
      expect(ADMIN_ROUTES.has(href), `missing route for nav href ${href}`).toBe(true);
      expect(href.startsWith('/platform/'), href).toBe(false);
      expect(href.split('/').filter(Boolean)).toHaveLength(1);
    }
  });

  it('keeps every registered admin route classified as an admin app route', () => {
    for (const href of ADMIN_ROUTES) {
      expect(isAdminAppRoute(href), href).toBe(true);
    }
  });

  it('builds external repository doc URLs for admin help links', () => {
    expect(repositoryDoc('docs/en/user/FIREWALL.md')).toBe(
      'https://github.com/techberode/paginiumcms-architecture/blob/main/docs/en/user/FIREWALL.md'
    );
  });
});
