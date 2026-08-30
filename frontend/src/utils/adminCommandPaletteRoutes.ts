// frontend/src/utils/adminCommandPaletteRoutes.ts
import {
  ADMIN_NAV_ANALYTICS_ITEM,
  ADMIN_NAV_PRIMARY_ITEM,
  ADMIN_NAV_SECTIONS,
} from '../config/adminNavSections';
import type { AdminSearchResultItem } from '../api/search';

const ADMIN_ROLES = new Set(['ADMIN', 'SUPER_ADMIN']);

function canSeeAdminOnlyRoute(roles: string[]): boolean {
  return roles.some((role) => ADMIN_ROLES.has(role));
}

/**
 * Client-side admin module catalog for command palette quick jumps (It.43).
 * Used when the query is empty/short so editors still see navigation targets.
 */
export function buildLocalAdminRouteItems(
  translate: (key: string) => string,
  query: string,
  roles: string[]
): AdminSearchResultItem[] {
  const q = query.trim().toLowerCase();
  const allowAdminOnly = canSeeAdminOnlyRoute(roles);
  const items: AdminSearchResultItem[] = [];

  const pushItem = (
    id: string,
    labelKey: string,
    href: string,
    adminOnly?: boolean
  ): void => {
    if (adminOnly && !allowAdminOnly) {
      return;
    }

    const title = translate(labelKey);
    const haystack = `${title} ${id} ${href}`.toLowerCase();
    if (q !== '' && !haystack.includes(q)) {
      return;
    }

    items.push({
      type: 'route',
      title,
      path: href,
      adminPath: href,
      routeId: id,
    });
  };

  pushItem(
    ADMIN_NAV_PRIMARY_ITEM.id,
    ADMIN_NAV_PRIMARY_ITEM.labelKey,
    ADMIN_NAV_PRIMARY_ITEM.href,
    ADMIN_NAV_PRIMARY_ITEM.adminOnly
  );
  pushItem(
    ADMIN_NAV_ANALYTICS_ITEM.id,
    ADMIN_NAV_ANALYTICS_ITEM.labelKey,
    ADMIN_NAV_ANALYTICS_ITEM.href,
    ADMIN_NAV_ANALYTICS_ITEM.adminOnly
  );

  for (const section of ADMIN_NAV_SECTIONS) {
    for (const item of section.items) {
      pushItem(item.id, item.labelKey, item.href, item.adminOnly);
    }
  }

  return items;
}

export default buildLocalAdminRouteItems;
