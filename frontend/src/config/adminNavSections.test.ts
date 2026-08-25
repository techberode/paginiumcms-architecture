import { describe, expect, it } from 'vitest';
import { ADMIN_NAV_ANALYTICS_ITEM, ADMIN_NAV_PRIMARY_ITEM, ADMIN_NAV_SECTIONS } from './adminNavSections';
import { repositoryDoc } from './repositoryDocs';

/** Routes registered under AdminShell in App.tsx (keep in sync when adding nav items). */
const ADMIN_ROUTES = new Set([
  '/dashboard',
  '/analytics',
  '/pages',
  '/articles',
  '/categories',
  '/platform/editorial-calendar',
  '/media',
  '/gallery',
  '/navigation',
  '/comments',
  '/messages',
  '/newsletter',
  '/settings',
  '/translations',
  '/users',
  '/platform/api-keys',
  '/platform/redirects',
  '/platform/webhooks',
  '/platform/shortcodes',
  '/platform/snippets',
  '/notifications',
  '/scheduler',
  '/platform/update',
  '/platform/origin',
  '/account/security',
  '/code-editor',
  '/blueprints',
  '/extensions',
  '/demo',
  '/firewall',
  '/logs',
  '/audit',
  '/security/audit',
  '/security/roles',
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
    }
  });

  it('builds external repository doc URLs for admin help links', () => {
    expect(repositoryDoc('docs/en/user/FIREWALL.md')).toBe(
      'https://github.com/techberode/paginiumcms-architecture/blob/main/docs/en/user/FIREWALL.md'
    );
  });
});
