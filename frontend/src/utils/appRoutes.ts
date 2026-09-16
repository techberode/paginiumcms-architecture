import {
  ADMIN_NAV_ANALYTICS_ITEM,
  ADMIN_NAV_PRIMARY_ITEM,
  ADMIN_NAV_SECTIONS,
} from '../config/adminNavSections';

/** Admin routes that are not sidebar items (account menu, preview, logs, legacy prefixes). */
const EXTRA_ADMIN_ROUTE_PREFIXES = ['/account', '/preview', '/developer', '/platform', '/security'] as const;

function firstSegmentPrefix(pathname: string): string | null {
  const segment = pathname.split('/').filter(Boolean)[0];
  return segment ? `/${segment}` : null;
}

/**
 * First-path-segment prefixes for admin chrome vs public appearance.
 * Derived from sidebar hrefs so new workspace items (categories, gallery, …)
 * keep the admin light/dark toggle and do not inherit the public color scheme.
 */
export const ADMIN_ROUTE_PREFIXES: string[] = [
  ...new Set(
    [
      ADMIN_NAV_PRIMARY_ITEM.href,
      ADMIN_NAV_ANALYTICS_ITEM.href,
      ...ADMIN_NAV_SECTIONS.flatMap((section) => section.items.map((item) => item.href)),
      ...EXTRA_ADMIN_ROUTE_PREFIXES,
    ]
      .map(firstSegmentPrefix)
      .filter((prefix): prefix is string => prefix !== null)
  ),
];

export function isAdminAppRoute(pathname: string): boolean {
  return ADMIN_ROUTE_PREFIXES.some(
    (prefix) => pathname === prefix || pathname.startsWith(`${prefix}/`)
  );
}
